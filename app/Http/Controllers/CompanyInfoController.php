<?php

namespace App\Http\Controllers;

use App\Services\GlassdoorService;
use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    public function __construct(private GlassdoorService $glassdoor) {}

    public function show(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'city' => 'sometimes|string|max:100',
        ]);

        $info = $this->glassdoor->companyInfo($request->name, $request->input('city', ''));

        return response()->json($info ?: (object) []);
    }
}
