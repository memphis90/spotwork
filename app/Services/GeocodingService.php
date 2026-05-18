<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeocodingService
{
    public function __construct(private ApiService $api){}

    public function geocode(string $city): array
    {
        $endpoint = "https://nominatim.openstreetmap.org/search?q={$city}&format=json";
        return Cache::remember('geo:' . Str::slug($city), 86400, fn() => $this->api->get($endpoint));
    }

    public function reverse(float $lat, float $lon): string
    {
        $endpoint = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lon}&format=json";
        $result   = Cache::remember("geo:rev:{$lat}:{$lon}", 86400, fn() => $this->api->get($endpoint));
        return $result['address']['city']
            ?? $result['address']['town']
            ?? $result['address']['village']
            ?? $result['display_name']
            ?? '';
    }
}
