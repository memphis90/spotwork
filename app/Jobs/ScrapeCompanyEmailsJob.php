<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\EmailScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeCompanyEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(public Company $company) {}

    public function handle(EmailScraperService $scraper): void
    {
        $email = $scraper->scrape($this->company->website);

        $this->company->update([
            'email'            => $email,
            'email_scraped_at' => now(),
        ]);
    }
}
