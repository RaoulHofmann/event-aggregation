<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetPostgresSchema;
use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\RequirePlatformAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prependToPriorityList(SubstituteBindings::class, SetPostgresSchema::class);
        $middleware->prependToPriorityList(SetPostgresSchema::class, RequireActiveAccount::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, RequirePlatformAdmin::class);
        $middleware->alias(['abilities' => CheckAbilities::class]);
        $middleware->web(append: [
            RequireActiveAccount::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['api_key', 'code', 'state', 'client_secret', 'access_token', 'refresh_token']);
        Integration::handles($exceptions);
    })->create();
