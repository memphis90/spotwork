<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JobSearchService
{
    public function __construct(
        private GeocodingService $geocoding,
    ) {}

    public function search(float $lat, float $lon, int $radius, array $keywords, string $rawCity = ''): array
    {
        $q        = implode(' ', $keywords) ?: 'offerte lavoro';
        $isItalia = strtolower(trim($rawCity)) === 'italia';
        $key      = $isItalia
            ? 'jobsearch:' . Str::slug($q) . ':italia'
            : 'jobsearch:' . Str::slug($q) . ":{$lat}:{$lon}:{$radius}";

        return Cache::remember($key, 3600, function () use ($lat, $lon, $q, $isItalia, $rawCity) {
            $serpLocation = $isItalia
                ? 'Italy'
                : trim(explode(',', $rawCity)[0]) . ', Italy';

            $client = new \GoogleSearchResults(config('services.serpapi.key'));
            $results = $this->querySerpapi($client, $q, $serpLocation);

            $grouped = collect($results['jobs_results'] ?? [])->groupBy('company_name');

            $companies = [];
            foreach ($grouped as $companyName => $companyJobs) {
                $jobLocation = $companyJobs->first()['location'] ?? $serpLocation;

                $geo  = $this->geocoding->geocode($jobLocation);
                $cLat = (float) ($geo[0]['lat'] ?? $lat);
                $cLon = (float) ($geo[0]['lon'] ?? $lon);

                $company = Company::upsertFromData([
                    'name'     => $companyName,
                    'lat'      => $cLat,
                    'lon'      => $cLon,
                    'category' => 'all',
                    'address'  => $jobLocation,
                    'email'    => null,
                    'source'   => 'serpapi',
                ]);

                $companies[] = [
                    'id'       => $company->id,
                    'name'     => $companyName,
                    'lat'      => $cLat,
                    'lon'      => $cLon,
                    'distance' => $this->haversineKm($lat, $lon, $cLat, $cLon),
                    'category' => 'all',
                    'address'  => $jobLocation,
                    'website'  => null,
                    'email'    => null,
                    'phone'    => null,
                    'size'     => null,
                    'hiring'   => true,
                    'jobs'     => $companyJobs->count(),
                ];
            }
            return $companies;
        });
    }

    private function querySerpapi(\GoogleSearchResults $client, string $q, string $location): array
    {
        $params = ['engine' => 'google_jobs', 'q' => $q, 'location' => $location, 'hl' => 'it', 'gl' => 'it'];
        try {
            return json_decode(json_encode($client->get_json($params)), true);
        } catch (\Throwable) {
            // SerpAPI rejects some location strings (regions, provinces).
            // Retry with Italy-wide search; distance filter in SearchController handles scope.
            try {
                $params['location'] = 'Italy';
                return json_decode(json_encode($client->get_json($params)), true);
            } catch (\Throwable $e) {
                \Log::warning('SerpAPI Google Jobs failed', ['error' => $e->getMessage()]);
                return [];
            }
        }
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
