<?php

namespace App\Services;

use GuzzleHttp\Client;

class ApiService
{
    public function __construct(private Client $client) {}

    public function get(string $url):array{
        try {
            $response = $this->client->request('GET', $url);
            return json_decode($response->getBody()->getContents(), true);

        }catch (\Exception $e){
            \Log::error('API call failed', ['url' => $url, 'error' => $e->getMessage()]);
            return [];

        }
    }

    public function post(string $url, string $body): array {
        try {
            $response = $this->client->request('POST', $url, ['body' => $body]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Exception $e) {
            \Log::error('API call failed', ['url' => $url, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getXml(string $url): \SimpleXMLElement|false {
        try {
            $response = $this->client->request('GET', $url);
            return simplexml_load_string($response->getBody()->getContents());
        } catch (\Exception $e) {
            \Log::error('API call failed', ['url' => $url, 'error' => $e->getMessage()]);
            return false;
        }

    }


}
