<?php

namespace App\Http\Controllers;

use App\Models\SavedCompany;
use App\Models\SavedJob;
use Inertia\Inertia;

class SavedController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $companies = SavedCompany::where('user_id', $userId)
            ->with('company')
            ->orderByDesc('saved_at')
            ->get()
            ->map(fn($s) => array_merge($s->company->toArray(), [
                'saved_id' => $s->id,
                'saved_at' => $s->saved_at,
            ]));

        $jobs = SavedJob::where('user_id', $userId)
            ->with('company')
            ->orderByDesc('saved_at')
            ->get()
            ->map(fn($s) => [
                'id'        => $s->id,
                'job_title' => $s->job_title,
                'job_url'   => $s->job_url,
                'saved_at'  => $s->saved_at,
                'company'   => $s->company?->only(['id', 'name', 'address', 'category']),
            ]);

        return Inertia::render('Saved', compact('companies', 'jobs'));
    }

    public function destroyCompany(SavedCompany $savedCompany)
    {
        abort_if($savedCompany->user_id !== auth()->id(), 403);
        $savedCompany->delete();
        return back();
    }

    public function destroyJob(SavedJob $savedJob)
    {
        abort_if($savedJob->user_id !== auth()->id(), 403);
        $savedJob->delete();
        return back();
    }
}
