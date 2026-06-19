<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\PortalJob;
use App\Models\UserCareerProfile;
use App\Services\CareerOpsClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanPortalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function handle(CareerOpsClient $client): void
    {
        $companies = Company::whereNotNull('portal_url')->get();
        if ($companies->isEmpty()) {
            return;
        }

        $watchedCompanies = $companies->map(fn ($c) => array_filter([
            'name' => $c->name,
            'careers_url' => $c->portal_url,
            'provider' => $c->ats_provider,
            'enabled' => true,
        ]))->values()->all();

        $jobs = $client->scan($watchedCompanies);
        $profiles = UserCareerProfile::where('is_active', true)->get();

        foreach ($jobs as $jobData) {
            $portalJob = PortalJob::updateOrCreate(
                ['url' => $jobData['url']],
                [
                    'company_name' => $jobData['company'] ?? '',
                    'title' => $jobData['title'],
                    'location' => $jobData['location'] ?? null,
                    'posted_at' => isset($jobData['postedAt'])
                        ? Carbon::createFromTimestampMs($jobData['postedAt'])
                        : null,
                ]
            );

            if ($portalJob->wasRecentlyCreated) {
                foreach ($profiles as $profile) {
                    EvaluatePortalJobJob::dispatch($portalJob->id, $profile->id);
                }
            }
        }
    }
}
