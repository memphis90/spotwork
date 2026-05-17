<?php

namespace App\Providers;

use App\Services\ApiService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApiService::class, fn() => new ApiService(new Client([
            'timeout' => 10,
            'headers' => ['User-Agent' => 'jobmap/1.0'],
        ])));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
