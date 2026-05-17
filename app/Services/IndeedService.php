<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IndeedService
{
    public function __construct(private ApiService $api){}

    public function getJobs(string $companyName, string $city):array{

        $endpoint = "https://it.indeed.com/rss?q='$companyName'&l=$city";
        $key = 'jobs:' . Str::slug($companyName) . ':' . Str::slug($city);

        return Cache::remember($key, 7200   , function() use ($endpoint) {
            $xml = $this->api->getXml($endpoint);
            if(!$xml) return [];

            $jobs = [];
            foreach($xml->channel->item as $item){
                $jobs[] = [
                    'title' => $item->title,
                    'link' => $item->link,
                    'company' => $item->children('indeed', true)->company??'',
                    'date' => $item->pubDate,
                ];
            }

            return $jobs;

        });






      //  return Cache::remember($key, $ttl, fn() => $this->fetchFromSource());



//        Metodo getJobs(string $companyName, string $city): array
//        Fetch RSS https://it.indeed.com/rss?q="Company"&l=City
//        Parsea XML con simplexml_load_string()
//        Cache key: jobs:{slug($companyName)}:{slug($city)} — TTL 2h
//
    }
}
