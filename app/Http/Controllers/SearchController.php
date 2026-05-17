<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use App\Services\IndeedService;
use App\Services\OverpassService;
use Illuminate\Support\Facades\Request;

class SearchController extends Controller
{
    private GeocodingService $geocoding;
    private OverpassService $overpass;
    private IndeedService $indeed;


    public function index(GeocodingService $geocoding, OverpassService $overpass, IndeedService $indeed){
        $this->geocoding = $geocoding;
        $this->overpass  = $overpass;
        $this->indeed    = $indeed;
    }

    public function search(Request $request){

        $request->validate([
            'city' => 'required|string|max:100',
            'radius' => 'required|integer|in:2000,5000,10000,25000,50000',
            'required|string|in:all,it,industry,retail,health,food,finance',
        ]);

        [$lat, $lon] = $this->geocoding->geocode($request->city);
        $companies   = $this->overpass->search($lat, $lon, $request->radius, $request->category);

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
