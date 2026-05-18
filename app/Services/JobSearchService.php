<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SerpApi\GoogleSearch;

class JobSearchService
{
    public function __construct(
        private GeocodingService $geocoding,
    ) {}

    public function search(float $lat, float $lon, int $radius, array $keywords): array
    {
        $q   = implode(' ', $keywords);
        $key = 'jobsearch:' . Str::slug($q) . ":{$lat}:{$lon}:{$radius}";

        return Cache::remember($key, 3600, function () use ($lat, $lon, $radius, $q, $keywords) {
            $city = $this->geocoding->reverse($lat, $lon);

            $client  = new GoogleSearch(['api_key' => config('services.serpapi.key')]);
            $results = $client->get_json([
                'engine'   => 'google_jobs',
                'q'        => $q,
                'location' => $city . ', Italy',
                'hl'       => 'it',
                'gl'       => 'it',
            ]);

            $jobs = $results['jobs_results'] ?? [];

            // Group by company, geocode each, return company shape
            $grouped = collect($jobs)->groupBy('company_name');

            $companies = [];
            foreach ($grouped as $companyName => $companyJobs) {
                $firstJob = $companyJobs->first();
                $location = $firstJob['location'] ?? $city;

                $geo   = $this->geocoding->geocode($location);
                $cLat  = (float) ($geo[0]['lat'] ?? $lat);
                $cLon  = (float) ($geo[0]['lon'] ?? $lon);

                $company = Company::upsertFromData([
                    'name'    => $companyName,
                    'lat'     => $cLat,
                    'lon'     => $cLon,
                    'category' => 'all',
                    'address' => $location,
                    'source'  => 'serpapi',
                ]);

                $companies[] = [
                    'id'       => $company->id,
                    'name'     => $companyName,
                    'lat'      => $cLat,
                    'lon'      => $cLon,
                    'distance' => $this->haversineKm($lat, $lon, $cLat, $cLon),
                    'category' => 'all',
                    'address'  => $location,
                    'website'  => null,
                    'phone'    => null,
                    'size'     => null,
                    'hiring'   => true,
                    'jobs'     => $companyJobs->count(),
                ];
            }

            return $companies;
        });
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
