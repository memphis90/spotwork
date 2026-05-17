<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeocodingService
{
    public function __construct(private ApiService $api){}

    public function geocode(string $city):array{
        $endpoint = "https://nominatim.openstreetmap.org/search?q={$city}&format=json";
        return Cache::remember('geo:' . Str::slug($city), 86400, function() use ($endpoint) {
           return $this->api->get($endpoint);
        });
    }
}
