<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use App\Services\IndeedService;
use App\Services\JobSearchService;
use App\Services\OverpassService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private GeocodingService $geocoding,
        private OverpassService  $overpass,
        private IndeedService    $indeed,
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

        if ($request->filled('keywords')) {
            $companies = $this->jobSearch->search($lat, $lon, (int) $request->radius, $request->keywords, $request->city);
            return response()->json(compact('lat', 'lon', 'companies'));
        }

        $raw       = $this->overpass->search($lat, $lon, (int) $request->radius, $request->category);
        $companies = collect($raw)->map(function ($el) use ($lat, $lon, $request) {
            $tags   = $el['tags'] ?? [];
            $elLat  = (float) ($el['lat'] ?? $el['center']['lat'] ?? 0);
            $elLon  = (float) ($el['lon'] ?? $el['center']['lon'] ?? 0);

            $address = collect([
                $tags['addr:street']      ?? null,
                $tags['addr:housenumber'] ?? null,
                $tags['addr:city']        ?? null,
            ])->filter()->implode(', ');

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
                'hiring'   => false,
                'jobs'     => 0,
            ];
        })->values()->toArray();

        return response()->json(compact('lat', 'lon', 'companies'));
    }

    public function jobs(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
        ]);

        $jobs = $this->indeed->getJobs($request->name, $request->city);
        return response()->json(['jobs' => $jobs]);
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
