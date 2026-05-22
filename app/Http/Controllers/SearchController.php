<?php

namespace App\Http\Controllers;

use App\Services\AdzunaService;
use App\Services\GeocodingService;
use App\Services\JobSearchService;
use App\Services\OverpassService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private GeocodingService $geocoding,
        private OverpassService  $overpass,
        private AdzunaService    $adzuna,
        private JobSearchService $jobSearch,
    ) {}

    public function search(Request $request)
    {
        $request->validate([
            'city'       => 'required|string|max:100',
            'radius'     => 'required|integer|in:2000,5000,10000,25000,50000',
            'category'   => 'required|string|in:all,it,industry,retail,health,food,finance',
            'keywords'   => 'sometimes|array|max:5',
            'keywords.*' => 'string|max:50|regex:/^[\pL\pN\s\-\.+#]+$/u',
        ]);

        $geo = $this->geocoding->geocode($request->city);
        $lat = (float) ($geo[0]['lat'] ?? 0);
        $lon = (float) ($geo[0]['lon'] ?? 0);

        $placeTypes = ['city', 'town', 'village', 'suburb', 'hamlet', 'locality', 'quarter', 'neighbourhood'];
        $geoType    = in_array($geo[0]['type'] ?? '', $placeTypes) ? 'city' : 'area';
        $bbox       = $geoType === 'area' ? ($geo[0]['boundingbox'] ?? null) : null;

        $osmId  = (int) ($geo[0]['osm_id'] ?? 0);
        $areaId = null;
        if ($geoType === 'area' && $osmId > 0) {
            $areaId = match ($geo[0]['osm_type'] ?? '') {
                'relation' => 3600000000 + $osmId,
                'way'      => 2400000000 + $osmId,
                default    => null,
            };
        }

        // 1. Always fetch geographic companies from Overpass.
        $raw = $this->overpass->search($lat, $lon, (int) $request->radius, $request->category, $areaId);

        // 2. Fetch job listings — Adzuna preferred, SerpAPI as fallback.
        $keywords    = $request->input('keywords', []);
        $enrichTerms = $this->buildEnrichTerms($request->category, $keywords);
        $hiringList  = [];
        $hiringByName = collect([]);
        try {
            $useAdzuna = config('services.adzuna.app_id') && config('services.adzuna.app_key');
            $adzunaList = $useAdzuna
                ? $this->adzuna->search($lat, $lon, (int) $request->radius, $enrichTerms, $request->city)
                : [];
            $serpList = $this->jobSearch->search($lat, $lon, (int) $request->radius, $enrichTerms, $request->city);

            // Merge both sources; if a company appears in both, keep the one with more jobs.
            $merged = collect(array_merge($adzunaList, $serpList))
                ->groupBy(fn($c) => $this->normalizeName($c['name']))
                ->map(fn($group) => $group->sortByDesc('jobs')->first())
                ->values();

            $hiringList   = $merged->toArray();
            $hiringByName = $merged
                ->keyBy(fn($c) => $this->normalizeName($c['name']))
                ->map(fn($c) => (int) $c['jobs']);
        } catch (\Throwable $e) {
            \Log::warning('Job enrichment failed', ['error' => $e->getMessage()]);
        }

        // 3. Build Overpass company list, enriching any that match a hiring source.
        $osmNormNames = collect($raw)
            ->map(fn($el) => $this->normalizeName($el['tags']['name'] ?? ''))
            ->filter()
            ->all();

        // Index hiring list by normalized name for job_url lookup.
        $hiringByNameFull = collect($hiringList)
            ->keyBy(fn($c) => $this->normalizeName($c['name']));

        $companies = collect($raw)->map(function ($el) use ($lat, $lon, $request, $hiringByName, $hiringByNameFull) {
            $tags   = $el['tags'] ?? [];
            $elLat  = (float) ($el['lat'] ?? $el['center']['lat'] ?? 0);
            $elLon  = (float) ($el['lon'] ?? $el['center']['lon'] ?? 0);

            $address = collect([
                $tags['addr:street']      ?? null,
                $tags['addr:housenumber'] ?? null,
                $tags['addr:city']        ?? null,
            ])->filter()->implode(', ');

            $normName    = $this->normalizeName($tags['name'] ?? '');
            $hiringCount = $this->resolveHiring($normName, $hiringByName);
            $jobUrl      = $hiringByNameFull->get($normName)['job_url'] ?? null;

            return [
                'id'       => $el['id'],
                'name'     => $tags['name'],
                'lat'      => $elLat,
                'lon'      => $elLon,
                'distance' => $this->haversineKm($lat, $lon, $elLat, $elLon),
                'category' => $this->detectCategory($tags, $request->category),
                'address'  => $address ?: null,
                'website'  => $this->normalizeUrl($tags['website'] ?? $tags['url'] ?? $tags['contact:website'] ?? null),
                'email'    => $tags['email'] ?? $tags['contact:email'] ?? null,
                'phone'    => $tags['phone'] ?? $tags['contact:phone'] ?? null,
                'size'     => $tags['employees'] ?? null,
                'hiring'   => $hiringCount > 0,
                'jobs'     => $hiringCount,
                'job_url'  => $jobUrl,
            ];
        })->values()->toArray();

        // 4. Append SerpAPI companies that have no OSM counterpart.
        foreach ($hiringList as $sc) {
            $scNorm = $this->normalizeName($sc['name']);
            $matched = false;
            if (strlen($scNorm) >= 4) {
                foreach ($osmNormNames as $osmNorm) {
                    if ($osmNorm === $scNorm ||
                        (strlen($osmNorm) >= 4 &&
                         (str_contains($osmNorm, $scNorm) || str_contains($scNorm, $osmNorm)))) {
                        $matched = true;
                        break;
                    }
                }
            }
            if (!$matched && $sc['distance'] <= $request->radius / 1000) {
                $companies[] = $sc;
            }
        }

        // 5. For area searches without a precise OSM boundary, trim to bbox.
        if ($bbox && $areaId === null) {
            [$south, $north, $west, $east] = array_map('floatval', $bbox);
            $companies = array_values(array_filter(
                $companies,
                fn($c) => $c['lat'] >= $south && $c['lat'] <= $north
                       && $c['lon'] >= $west  && $c['lon'] <= $east
            ));
        }

        return response()->json(compact('lat', 'lon', 'geoType', 'companies'));
    }

    private function resolveHiring(string $normalizedName, \Illuminate\Support\Collection $hiringByName): int
    {
        // 1. Exact normalized match
        if ($hiringByName->has($normalizedName)) {
            return (int) $hiringByName->get($normalizedName, 0);
        }

        // 2. Substring match: "microsoft" matches "microsoft italia" and vice versa
        if (strlen($normalizedName) >= 4) {
            foreach ($hiringByName as $indeedName => $count) {
                if (strlen((string) $indeedName) >= 4 &&
                    (str_contains($normalizedName, (string) $indeedName) || str_contains((string) $indeedName, $normalizedName))) {
                    return (int) $count;
                }
            }
        }

        return 0;
    }

    private function buildEnrichTerms(string $category, array $keywords): array
    {
        $base = match($category) {
            'it'       => 'informatica',
            'industry' => 'industria',
            'retail'   => 'commercio',
            'health'   => 'sanità',
            'food'     => 'ristorazione',
            'finance'  => 'finanza',
            default    => null,
        };

        if ($base !== null) {
            return array_merge([$base], $keywords);
        }

        // Category 'all' with no keywords: use a generic Italian hiring term.
        return $keywords ?: ['assunzioni'];
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (!$url) return null;
        // strip any duplicate protocol (e.g. "https://https://..." or "https://http://...")
        $url = preg_replace('#^(https?://)+(https?://)#', '$2', $url);
        // ensure it starts with a protocol
        if (!preg_match('#^https?://#', $url)) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/') ?: null;
    }

    private function normalizeName(string $name): string
    {
        // Strip common Italian/EU legal suffixes for fuzzy name matching.
        $cleaned = preg_replace(
            '/\b(s\.?\s*p\.?\s*a\.?|s\.?\s*r\.?\s*l\.?|s\.?\s*n\.?\s*c\.?|s\.?\s*a\.?\s*s\.?|ltd\.?|inc\.?|gmbh|group|spa|srl|onlus)\b/iu',
            '',
            $name
        );
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($cleaned)));
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    private function detectCategory(array $tags, string $requested): string
    {
        if ($requested !== 'all') return $requested;

        $office  = $tags['office']   ?? '';
        $amenity = $tags['amenity']  ?? '';
        $manMade = $tags['man_made'] ?? '';

        if ($tags['shop']        ?? false) return 'retail';
        if ($tags['healthcare']  ?? false) return 'health';
        if (preg_match('/it|software|computer|coworking/i', $office))              return 'it';
        if (preg_match('/works|factory/i', $manMade))                              return 'industry';
        if (preg_match('/hospital|clinic|doctors|dentist|pharmacy/i', $amenity))   return 'health';
        if (preg_match('/restaurant|cafe|fast_food|bar|pub/i', $amenity))          return 'food';
        if (preg_match('/bank|bureau_de_change/i', $amenity))                      return 'finance';
        return 'all';
    }
}
