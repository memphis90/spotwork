<?php

namespace App\Console\Commands;

use App\Jobs\ScanPortalsJob;
use Illuminate\Console\Command;

class ScanPortals extends Command
{
    protected $signature = 'portals:scan';

    protected $description = 'Dispatch a background job to scan all company portals for new openings.';

    public function handle(): int
    {
        ScanPortalsJob::dispatch();
        $this->info('Portal scan dispatched.');

        return self::SUCCESS;
    }
}
