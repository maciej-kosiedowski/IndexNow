<?php

namespace SlimAD\IndexNow\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SlimAD\IndexNow\Clients\HttpIndexNowClient;
use SlimAD\IndexNow\Contracts\IndexNowClient;
use SlimAD\IndexNow\Controllers\IndexNowController;
use SlimAD\IndexNow\Jobs\IndexNowSubmitJob;
use SlimAD\IndexNow\Services\IndexNowService;

class IndexNowServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/indexnow.php', 'indexnow'
        );

        // Bindings
        $this->app->bind(IndexNowClient::class, HttpIndexNowClient::class);
        $this->app->singleton(IndexNowService::class, function ($app) {
            return new IndexNowService();
        });
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/indexnow.php' => config_path('indexnow.php'),
        ], 'indexnow-config');

        // Register Route for Key Location
        // The URL needs to be the key itself with .txt, e.g. /12345.txt
        // Restricted regex to avoid greedy catch-all route that could intercept other .txt requests
        Route::get('/{key}.txt', [IndexNowController::class, 'keyLocation'])
            ->where('key', '[a-zA-Z0-9\-]+');

        // Schedule Job
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $cronExpr = config('indexnow.schedule', '0 * * * *');

            $schedule->job(new IndexNowSubmitJob)->cron($cronExpr);
        });
    }
}
