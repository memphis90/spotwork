<?php

namespace Tests\Feature\Commands;

use App\Jobs\ScanPortalsJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanPortalsCommandTest extends TestCase
{
    public function test_dispatches_scan_portals_job(): void
    {
        Queue::fake();

        $this->artisan('portals:scan')
            ->expectsOutput('Portal scan dispatched.')
            ->assertExitCode(0);

        Queue::assertPushed(ScanPortalsJob::class);
    }
}
