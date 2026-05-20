<?php

namespace App\Providers;

use App\Services\ApiService;
use App\Services\EmailScraperService;
use App\Services\GeocodingService;
use App\Services\JobSearchService;
use GuzzleHttp\Client;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->bind(EmailScraperService::class, fn() =>
            new EmailScraperService(new Client(['timeout' => 15]))
        );
    }

    public function boot(): void
    {
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('jobs', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
