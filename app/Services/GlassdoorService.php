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
            $client = new \GoogleSearchResults(config('services.serpapi.key'));

            $params = [
                'engine'   => 'glassdoor',
                'q'        => $name,
                'hl'       => 'it',
                'gl'       => 'it',
            ];
            if ($city) {
                $params['location'] = trim(explode(',', $city)[0]) . ', Italy';
            }

            $results = json_decode(json_encode($client->get_json($params)), true);

            $company = collect($results['organic_results'] ?? [])
                ->first(fn($r) => isset($r['overall_rating']));

            if (!$company) {
                return [];
            }

            return [
                'rating'      => round((float) ($company['overall_rating'] ?? 0), 1),
                'reviews'     => (int) ($company['reviews_count'] ?? 0),
                'url'         => $company['url'] ?? null,
                'ceo_rating'  => isset($company['ceo']['approval_rate'])
                    ? (int) $company['ceo']['approval_rate']
                    : null,
            ];
        });
    }
}
