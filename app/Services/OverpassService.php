<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OverpassService
{
    private const EXCLUDED_AMENITIES = [
        'school', 'university', 'college', 'kindergarten',
        'place_of_worship', 'community_centre', 'social_facility',
        'library', 'townhall', 'prison', 'courthouse',
    ];

    private const EXCLUDED_OFFICES = [
        'trade_union', 'labour', 'ngo', 'association',
        'charity', 'political_party', 'religion', 'foundation',
        'educational_institution',
    ];

    private const CATEGORY_FILTERS = [
        'it'       => '"office"~"it|software|computer|coworking"',
        'industry' => '"man_made"~"works|factory"',
        'retail'   => '"shop"',
        'health'   => '"amenity"~"hospital|clinic|doctors|dentist|pharmacy"',
        'food'     => '"amenity"~"restaurant|cafe|fast_food|bar|pub"',
        'finance'  => '"amenity"~"bank|bureau_de_change"',
    ];

    public function __construct(private ApiService $api) {}

    public function search(float $lat, float $lon, int $radius, string $category, ?int $areaId = null): array
    {
        $key = $areaId
            ? "search:area:{$areaId}:{$category}"
            : "search:{$lat}:{$lon}:{$radius}:{$category}";

        return Cache::remember($key, 21600, function () use ($lat, $lon, $radius, $category, $areaId) {
            $parts = [];

            if ($areaId) {
                // Query within exact OSM administrative boundary — no bbox bleed.
                $filters = $category === 'all'
                    ? array_values(self::CATEGORY_FILTERS)
                    : [self::CATEGORY_FILTERS[$category] ?? '"office"'];

                foreach ($filters as $f) {
                    $parts[] = "node[{$f}](area.a);";
                    $parts[] = "way[{$f}](area.a);";
                }
                $overpassQuery = '[out:json][timeout:45];area(id:' . $areaId . ')->.a;(' . implode('', $parts) . ');out 400 center;';
                $httpTimeout   = 55;
            } else {
                $filters = $category === 'all'
                    ? array_values(self::CATEGORY_FILTERS)
                    : [self::CATEGORY_FILTERS[$category] ?? '"office"'];

                foreach ($filters as $f) {
                    $parts[] = "node[{$f}](around:{$radius},{$lat},{$lon});";
                    $parts[] = "way[{$f}](around:{$radius},{$lat},{$lon});";
                }
                $overpassQuery = '[out:json][timeout:25];(' . implode('', $parts) . ');out center;';
                $httpTimeout   = 30;
            }

            $result = $this->api->post('https://overpass-api.de/api/interpreter', $overpassQuery, $httpTimeout);

            return collect($result['elements'] ?? [])
                ->filter(fn($el) => !empty($el['tags']['name']))
                ->filter(function ($el) {
                    $tags    = $el['tags'] ?? [];
                    $amenity = $tags['amenity'] ?? '';
                    $office  = $tags['office']  ?? '';
                    return !in_array($amenity, self::EXCLUDED_AMENITIES)
                        && !in_array($office,  self::EXCLUDED_OFFICES);
                })
                ->values()
                ->toArray();
        });
    }
}