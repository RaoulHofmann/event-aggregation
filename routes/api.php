<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
use App\Http\Middleware\SetPostgresSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified', SetPostgresSchema::class, 'abilities:read'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/events', [EventController::class, 'get']);
    Route::get('/v1/occurrences', [EventController::class, 'occurrences'])->middleware('throttle:60,1')->name('api.v1.occurrences');
    Route::get('/v1/events', [EventController::class, 'versioned'])->middleware('throttle:60,1')->name('api.v1.events');
    Route::get('/event-templates', [EventTemplateController::class, 'get']);
    Route::get('/field-templates', [FieldTemplateController::class, 'get']);
});
