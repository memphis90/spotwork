<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class EmailScraperService
{
    private const PATHS   = ['', '/contatti', '/contact', '/chi-siamo'];
    private const PATTERN = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';
    private const SKIP    = ['noreply', 'no-reply', 'donotreply'];

    public function __construct(private Client $client) {}

    public function scrape(string $website): ?string
    {
        $base = rtrim($website, '/');
        if (!str_starts_with($base, 'http')) {
            $base = 'https://' . $base;
        }

        foreach (self::PATHS as $path) {
            $url = 'https://api.scraperapi.com?' . http_build_query([
                'api_key' => config('services.scraperapi.key'),
                'url'     => $base . $path,
            ]);

            try {
                $html = (string) $this->client->get($url, ['timeout' => 15])->getBody();
                if (preg_match_all(self::PATTERN, $html, $matches)) {
                    foreach ($matches[0] as $email) {
                        foreach (self::SKIP as $word) {
                            if (str_contains(strtolower($email), $word)) continue 2;
                        }
                        return $email;
                    }
                }
            } catch (GuzzleException) {
                continue;
            }
        }

        return null;
    }
}
