<?php

namespace App\Providers;

use App\Http\Controllers\ApiTokenController;
use App\Models\ApiToken;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Http\Controllers\Inertia\ApiTokenController as JetstreamTokenController;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(JetstreamTokenController::class, ApiTokenController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(ApiToken::class);
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
