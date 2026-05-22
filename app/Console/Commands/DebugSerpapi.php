<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugSerpapi extends Command
{
    protected $signature   = 'debug:serpapi {company} {city=Milano}';
    protected $description = 'Dump raw SerpAPI and Adzuna responses for a company name';

    public function handle(): void
    {
        $company = $this->argument('company');
        $city    = $this->argument('city');
        $key     = config('services.serpapi.key');

        if (!$key) {
            $this->error('SERPAPI_KEY not set in .env');
            return;
        }

        $client = new \GoogleSearchResults($key);

        // ── Google Jobs ──────────────────────────────────────────────────────
        $this->info("=== Google Jobs: q=\"{$company}\" location=\"{$city}, Italy\" ===");
        try {
            $jobs = json_decode(json_encode($client->get_json([
                'engine'   => 'google_jobs',
                'q'        => $company,
                'location' => "{$city}, Italy",
                'hl'       => 'it',
                'gl'       => 'it',
            ])), true);
        } catch (\Throwable $e) {
            $this->error('Google Jobs error: ' . $e->getMessage());
            $jobs = [];
        }

        $jobResults = $jobs['jobs_results'] ?? [];
        $this->line('Jobs found: ' . count($jobResults));
        foreach (array_slice($jobResults, 0, 5) as $j) {
            $this->line("  [{$j['company_name']}] {$j['title']} — {$j['location']}");
        }

        // ── Glassdoor ────────────────────────────────────────────────────────
        $this->info("\n=== Glassdoor: q=\"{$company}\" location=\"{$city}, Italy\" ===");
        try {
            $gd = json_decode(json_encode($client->get_json([
                'engine'   => 'glassdoor',
                'q'        => $company,
                'location' => "{$city}, Italy",
                'hl'       => 'it',
                'gl'       => 'it',
            ])), true);
        } catch (\Throwable $e) {
            $this->error('Glassdoor error: ' . $e->getMessage());
            $gd = [];
        }

        $this->line('Raw keys: ' . implode(', ', array_keys($gd)));
        $organic = $gd['organic_results'] ?? [];
        $this->line('Organic results: ' . count($organic));
        foreach (array_slice($organic, 0, 3) as $r) {
            $this->line('  Keys: ' . implode(', ', array_keys($r)));
            $this->line('  ' . json_encode(array_intersect_key($r, array_flip([
                'name', 'overall_rating', 'rating', 'reviews_count', 'url',
            ]))));
        }

        // ── Adzuna ───────────────────────────────────────────────────────────
        $this->info("\n=== Adzuna: what=\"{$company}\" where=\"{$city}\" ===");
        $appId  = config('services.adzuna.app_id');
        $appKey = config('services.adzuna.app_key');
        if (!$appId || !$appKey) {
            $this->error('ADZUNA_APP_ID or ADZUNA_APP_KEY not set');
        } else {
            $resp = \Illuminate\Support\Facades\Http::timeout(10)->get(
                'https://api.adzuna.com/v1/api/jobs/it/search/1',
                [
                    'app_id'           => $appId,
                    'app_key'          => $appKey,
                    'what'             => $company,
                    'where'            => $city,
                    'distance'         => 30,
                    'results_per_page' => 10,
                ]
            );
            $this->line('HTTP status: ' . $resp->status());
            $results = $resp->json('results') ?? [];
            $this->line('Jobs found: ' . count($results));
            foreach (array_slice($results, 0, 5) as $j) {
                $this->line("  [{$j['company']['display_name']}] {$j['title']} — {$j['location']['display_name']}");
            }
            if ($resp->status() !== 200) {
                $this->line('Response body: ' . $resp->body());
            }
        }
    }
}
