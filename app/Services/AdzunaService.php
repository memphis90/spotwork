<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdzunaService
{
    private const BASE = 'https://api.adzuna.com/v1/api/jobs/it/search/1';

    public function search(float $lat, float $lon, int $radius, array $keywords, string $rawCity = ''): array
    {
        $what       = implode(' ', $keywords) ?: 'offerte lavoro';
        $where      = trim(explode(',', $rawCity)[0]);
        $distanceKm = max(5, (int) round($radius / 1000));
        $key        = 'adzuna:' . Str::slug($what) . ':' . Str::slug($where) . ':' . $distanceKm;

        return Cache::remember($key, 3600, function () use ($lat, $lon, $what, $where, $distanceKm) {
            $response = Http::timeout(15)->get(self::BASE, [
                'app_id'           => config('services.adzuna.app_id'),
                'app_key'          => config('services.adzuna.app_key'),
                'what'             => $what,
                'where'            => $where,
                'distance'         => $distanceKm,
                'results_per_page' => 50,
            ]);

            if (!$response->successful()) {
                \Log::warning('Adzuna search failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [];
            }

            $jobs = $response->json('results') ?? [];

            // Group by company, use coordinates from first job listing.
            $grouped = collect($jobs)->groupBy(fn($j) => $j['company']['display_name'] ?? '');

            return $grouped
                ->filter(fn($_, $name) => $name !== '')
                ->map(function ($companyJobs, $companyName) use ($lat, $lon) {
                    $first  = $companyJobs->first();
                    $cLat   = (float) ($first['latitude']  ?? $lat);
                    $cLon   = (float) ($first['longitude'] ?? $lon);

                    return [
                        'id'       => 'adzuna_' . Str::slug($companyName),
                        'name'     => $companyName,
                        'lat'      => $cLat,
                        'lon'      => $cLon,
                        'distance' => $this->haversineKm($lat, $lon, $cLat, $cLon),
                        'category' => 'all',
                        'address'  => $first['location']['display_name'] ?? null,
                        'website'  => null,
                        'email'    => null,
                        'phone'    => null,
                        'size'     => null,
                        'hiring'   => true,
                        'jobs'     => $companyJobs->count(),
                        'job_url'  => $first['redirect_url'] ?? null,
                    ];
                })
                ->values()
                ->toArray();
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
