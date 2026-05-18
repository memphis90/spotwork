<?php

namespace App\Providers;

use App\Services\ApiService;
use App\Services\GeocodingService;
use App\Services\JobSearchService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApiService::class, fn() => new ApiService(new Client([
            'timeout' => 10,
            'headers' => ['User-Agent' => 'jobmap/1.0'],
        ])));

        $this->app->bind(JobSearchService::class, fn() => new JobSearchService(
            app(GeocodingService::class),
        ));
    }

    public function boot(): void
    {
    }
}
