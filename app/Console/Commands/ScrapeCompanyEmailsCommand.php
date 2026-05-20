<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeCompanyEmailsJob;
use App\Models\Company;
use Illuminate\Console\Command;

class ScrapeCompanyEmailsCommand extends Command
{
    protected $signature   = 'companies:scrape-emails {--limit=200}';
    protected $description = 'Dispatch email scraping jobs for companies without email';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $companies = Company::query()
            ->whereNotNull('website')
            ->whereNull('email')
            ->where(function ($q) {
                $q->whereNull('email_scraped_at')
                  ->orWhere('email_scraped_at', '<', now()->subDays(30));
            })
            ->leftJoin('saved_companies', 'companies.id', '=', 'saved_companies.company_id')
            ->groupBy('companies.id')
            ->orderByRaw('COUNT(saved_companies.id) DESC')
            ->select('companies.*')
            ->limit($limit)
            ->get();

        foreach ($companies as $company) {
            ScrapeCompanyEmailsJob::dispatch($company);
        }

        $this->info("Dispatched {$companies->count()} jobs.");
        return self::SUCCESS;
    }
}
