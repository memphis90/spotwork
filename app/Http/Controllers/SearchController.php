<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use App\Services\IndeedService;
use App\Services\OverpassService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private GeocodingService $geocoding,
        private OverpassService  $overpass,
        private IndeedService    $indeed,
    ) {}

    public function search(Request $request)
    {
        $request->validate([
            'city'     => 'required|string|max:100',
            'radius'   => 'required|integer|in:2000,5000,10000,25000,50000',
            'category' => 'required|string|in:all,it,industry,retail,health,food,finance',
        ]);

        $geo     = $this->geocoding->geocode($request->city);
        $lat     = (float) ($geo[0]['lat'] ?? 0);
        $lon     = (float) ($geo[0]['lon'] ?? 0);
        $companies = $this->overpass->search($lat, $lon, (int) $request->radius, $request->category);

        return response()->json(compact('lat', 'lon', 'companies'));
    }

    public function jobs(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
        ]);

        $jobs = $this->indeed->getJobs($request->name, $request->city);
        return response()->json(['jobs' => $jobs]);
    }
}
