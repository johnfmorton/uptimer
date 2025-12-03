<?php

namespace App\Providers;

use App\Services\CheckService;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind CheckService with NotificationService dependency
        $this->app->singleton(CheckService::class, function ($app) {
            return new CheckService($app->make(NotificationService::class));
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
