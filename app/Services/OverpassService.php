<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OverpassService
{
    private const CATEGORY_FILTERS = [
        'it'       => '"office"~"it|software|computer|coworking"',
        'industry' => '"man_made"~"works|factory"',
        'retail'   => '"shop"',
        'health'   => '"amenity"~"hospital|clinic|doctors|dentist|pharmacy"',
        'food'     => '"amenity"~"restaurant|cafe|fast_food|bar|pub"',
        'finance'  => '"amenity"~"bank|bureau_de_change"',
    ];

    public function __construct(private ApiService $api) {}

    public function search(float $lat, float $lon, int $radius, string $category): array
    {
        $key = "search:{$lat}:{$lon}:{$radius}:{$category}";
        return Cache::remember($key, 21600, function () use ($lat, $lon, $radius, $category) {
            $filters = $category === 'all'
                ? array_values(self::CATEGORY_FILTERS)
                : [self::CATEGORY_FILTERS[$category] ?? '"office"'];

            $parts = [];
            foreach ($filters as $f) {
                $parts[] = "node[{$f}](around:{$radius},{$lat},{$lon});";
                $parts[] = "way[{$f}](around:{$radius},{$lat},{$lon});";
            }

            $overpassQuery = '[out:json][timeout:25];(' . implode('', $parts) . ');out center;';
            $result = $this->api->post('https://overpass-api.de/api/interpreter', $overpassQuery);

            return collect($result['elements'] ?? [])
                ->filter(fn($el) => !empty($el['tags']['name']))
                ->values()
                ->toArray();
        });
    }
}