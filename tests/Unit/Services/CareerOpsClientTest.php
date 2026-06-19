<?php

namespace Tests\Unit\Services;

use App\Services\CareerOpsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerOpsClientTest extends TestCase
{
    public function test_scan_returns_jobs_array(): void
    {
        Http::fake([
            'localhost:3001/scan' => Http::response([
                'jobs' => [
                    ['title' => 'PHP Dev', 'url' => 'https://example.com/job/1', 'company' => 'Acme', 'location' => 'Remote'],
                ],
            ], 200),
        ]);

        $client = new CareerOpsClient;
        $jobs = $client->scan([['name' => 'Acme', 'careers_url' => 'https://acme.com/careers', 'enabled' => true]]);

        Http::assertSent(fn ($req) => $req->url() === 'http://localhost:3001/scan');
        $this->assertCount(1, $jobs);
        $this->assertEquals('PHP Dev', $jobs[0]['title']);
    }

    public function test_fetch_job_description_returns_string(): void
    {
        Http::fake([
            'localhost:3001/fetch-jd' => Http::response(['description' => 'We are looking for...'], 200),
        ]);

        $client = new CareerOpsClient;
        $desc = $client->fetchJobDescription('https://example.com/job/1');

        $this->assertEquals('We are looking for...', $desc);
    }

    public function test_scan_sends_api_key_header_when_configured(): void
    {
        config(['services.career_ops.key' => 'test-key']);

        Http::fake([
            'localhost:3001/scan' => Http::response(['jobs' => []], 200),
        ]);

        $client = new CareerOpsClient;
        $client->scan([['name' => 'Acme', 'careers_url' => 'https://acme.com/careers']]);

        Http::assertSent(fn ($req) => $req->header('X-Api-Key')[0] === 'test-key');
    }
}
