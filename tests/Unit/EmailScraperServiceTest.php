<?php

namespace Tests\Unit;

use App\Services\EmailScraperService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class EmailScraperServiceTest extends TestCase
{
    private function makeService(array $responses): EmailScraperService
    {
        $mock   = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        return new EmailScraperService($client);
    }

    public function test_extracts_email_from_homepage(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>Contatti: <a href="mailto:info@acme.it">info@acme.it</a></html>'),
        ]);

        $this->assertSame('info@acme.it', $service->scrape('https://www.acme.it'));
    }

    public function test_tries_contact_page_if_homepage_has_no_email(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>No email here</html>'),
            new Response(200, [], '<html>Email: info@acme.it</html>'),
        ]);

        $this->assertSame('info@acme.it', $service->scrape('https://www.acme.it'));
    }

    public function test_returns_null_if_no_email_found(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>No email</html>'),
            new Response(200, [], '<html>No email</html>'),
            new Response(200, [], '<html>No email</html>'),
            new Response(200, [], '<html>No email</html>'),
        ]);

        $this->assertNull($service->scrape('https://www.acme.it'));
    }

    public function test_skips_noreply_emails(): void
    {
        $service = $this->makeService([
            new Response(200, [], '<html>noreply@acme.it</html>'),
            new Response(200, [], '<html>info@acme.it</html>'),
        ]);

        $this->assertSame('info@acme.it', $service->scrape('https://www.acme.it'));
    }

    public function test_handles_network_errors_gracefully(): void
    {
        $service = $this->makeService([
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('GET', 'test')),
        ]);

        $this->assertNull($service->scrape('https://www.acme.it'));
    }
}
