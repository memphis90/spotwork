<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IndeedService
{
    public function __construct(private ApiService $api) {}

    public function getJobs(string $companyName, string $city): array
    {
        $endpoint = $this->proxyUrl("https://it.indeed.com/rss?q=" . urlencode("\"$companyName\"") . "&l=" . urlencode($city));
        $key      = 'jobs:' . Str::slug($companyName) . ':' . Str::slug($city);

        return Cache::remember($key, 7200, function () use ($endpoint) {
            $xml = $this->api->getXml($endpoint);
            if (!$xml) return [];

            $jobs = [];
            foreach ($xml->channel->item as $item) {
                $indeed  = $item->children('indeed', true);
                $jobs[] = [
                    'title'   => (string) $item->title,
                    'url'     => (string) $item->link,
                    'company' => (string) ($indeed->company ?? ''),
                    'salary'  => (string) ($indeed->salary ?? ''),
                    'type'    => (string) ($indeed->jobtype ?? ''),
                    'posted'  => (string) $item->pubDate,
                ];
            }
            return $jobs;
        });
    }

    private function proxyUrl(string $url): string
    {
        $key = config('services.scraperapi.key');
        if (!$key) return $url;
        // render=false: proxy raw HTTP response without JS rendering (faster, cheaper for RSS/XML)
        return 'http://api.scraperapi.com?' . http_build_query(['api_key' => $key, 'url' => $url, 'render' => 'false']);
    }
}