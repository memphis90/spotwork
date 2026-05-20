<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function suggestEmail(Request $request, Company $company): \Illuminate\Http\JsonResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        if ($company->email) {
            return response()->json(['message' => 'Email già presente.'], 422);
        }

        $company->update([
            'email'            => $request->email,
            'email_scraped_at' => now(),
        ]);

        return response()->json(['message' => 'Grazie! Email aggiunta.']);
    }
}
