<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GlassdoorService
{
    public function companyInfo(string $name, string $city = ''): array
    {
        $key = 'glassdoor:' . Str::slug($name) . ':' . Str::slug($city);

        return Cache::remember($key, 86400, function () use ($name, $city) {
            $client   = new \GoogleSearchResults(config('services.serpapi.key'));
            $location = $city ? trim(explode(',', $city)[0]) . ', Italy' : 'Italy';

            $results  = $this->queryGlassdoor($client, $name, $location);

            $company = collect($results['organic_results'] ?? [])
                ->first(fn($r) => isset($r['overall_rating']) || isset($r['rating']));

            if (!$company) {
                return [];
            }

            $rating = (float) ($company['overall_rating'] ?? $company['rating'] ?? 0);

            return [
                'rating'  => round($rating, 1),
                'reviews' => (int) ($company['reviews_count'] ?? $company['number_of_reviews'] ?? 0),
                'url'     => $company['url'] ?? $company['link'] ?? null,
            ];
        });
    }

    private function queryGlassdoor(\GoogleSearchResults $client, string $q, string $location): array
    {
        $params = ['engine' => 'glassdoor', 'q' => $q, 'location' => $location, 'hl' => 'it', 'gl' => 'it'];
        try {
            return json_decode(json_encode($client->get_json($params)), true);
        } catch (\Throwable) {
            try {
                $params['location'] = 'Italy';
                return json_decode(json_encode($client->get_json($params)), true);
            } catch (\Throwable $e) {
                \Log::warning('SerpAPI Glassdoor failed', ['error' => $e->getMessage()]);
                return [];
            }
        }
    }
}
