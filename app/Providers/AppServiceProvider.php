<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use GuzzleHttp\Client as HttpClient;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // GeminiService as scoped (no constructor dependencies)
        $this->app->scoped(\App\Services\GeminiService::class);

        // SpeechToTextService as scoped with Filesystem dependency
        $this->app->scoped(\App\Services\SpeechToTextService::class, function (Application $app) {
            /** @var Filesystem $filesystem */
            $filesystem = $app->make(Filesystem::class);
            // Use local disk (assuming default) via adapter pathing; filesystem already represents disk
            return new \App\Services\SpeechToTextService($filesystem);
        });

        // YoutubeTranscriptService as scoped with HTTP and PSR-17 factories
        $this->app->scoped(\App\Services\YoutubeTranscriptService::class, function (Application $app) {
            $httpClient = new HttpClient(['timeout' => 30, 'verify' => true]);
            $requestFactory = new RequestFactory();
            $streamFactory = new StreamFactory();
            return new \App\Services\YoutubeTranscriptService($httpClient, $requestFactory, $streamFactory);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
