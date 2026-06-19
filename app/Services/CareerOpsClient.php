<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CareerOpsClient
{
    private string $baseUrl;

    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.career_ops.url', 'http://localhost:3001'), '/');
        $this->apiKey = config('services.career_ops.key');
    }

    public function scan(array $watchedCompanies): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->timeout(30)
            ->post("{$this->baseUrl}/scan", ['watched_companies' => $watchedCompanies]);

        $response->throw();

        return $response->json('jobs', []);
    }

    public function fetchJobDescription(string $url): string
    {
        $response = Http::withHeaders($this->authHeaders())
            ->timeout(20)
            ->post("{$this->baseUrl}/fetch-jd", ['url' => $url]);

        $response->throw();

        return $response->json('description', '');
    }

    private function authHeaders(): array
    {
        return $this->apiKey ? ['X-Api-Key' => $this->apiKey] : [];
    }
}
