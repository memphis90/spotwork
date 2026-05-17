<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OverpassService
{
    public function __construct(private ApiService $api){
    }

    public function search(float $lat, float $lon,int $radius, string $query):array{
        return Cache::remember('search:' . $lat . ':' . $lon . ':' . $radius . ':' . Str::slug($query), 21600, function() use ($lat, $lon, $radius, $query) {
            $overpassQuery = "[out:json][timeout:25];(node[{$query}](around:{$radius},{$lat},{$lon}););out;";
            $result = $this->api->post('https://overpass-api.de/api/interpreter', $overpassQuery);
            return collect($result['elements'] ?? [])
                      ->filter(fn($el) => !empty($el['tags']['name']))
                      ->values()
                      ->toArray();
              });
        }
    }
