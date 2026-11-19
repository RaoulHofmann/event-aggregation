<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
use App\Http\Middleware\AuthorizeApiWrite;
use App\Http\Middleware\SetPostgresSchema;
use App\Http\Middleware\RequireActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified', RequireActiveAccount::class, SetPostgresSchema::class, 'abilities:read'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/events', [EventController::class, 'get']);
    Route::get('/v1/event-templates/{eventTemplate}', [EventTemplateController::class, 'apiShow'])->whereNumber('eventTemplate')->middleware('throttle:60,1')->name('api.v1.event-templates.show');
    Route::get('/v1/field-templates/{fieldTemplate}', [FieldTemplateController::class, 'apiShow'])->whereNumber('fieldTemplate')->middleware('throttle:60,1')->name('api.v1.field-templates.show');
    Route::get('/event-templates', [EventTemplateController::class, 'get']);
    Route::get('/field-templates', [FieldTemplateController::class, 'get']);
});

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified', RequireActiveAccount::class, SetPostgresSchema::class, 'ability:read,events:read', 'throttle:60,1'])->group(function () {
    Route::get('/v1/occurrences', [EventController::class, 'occurrences'])->name('api.v1.occurrences');
    Route::get('/v1/events', [EventController::class, 'versioned'])->name('api.v1.events');
    Route::get('/v1/events/{event}', [EventController::class, 'apiShow'])->whereNumber('event')->name('api.v1.events.show');
});

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'verified', RequireActiveAccount::class, SetPostgresSchema::class, 'throttle:60,1'])->group(function () {
    Route::post('/events', [EventController::class, 'store'])->middleware(AuthorizeApiWrite::class.':events:create')->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'update'])->whereNumber('event')->middleware(AuthorizeApiWrite::class.':events:update')->name('events.update');
    Route::post('/event-templates', [EventTemplateController::class, 'store'])->middleware(AuthorizeApiWrite::class.':templates:create')->name('event-templates.store');
    Route::put('/event-templates/{eventTemplate}', [EventTemplateController::class, 'update'])->whereNumber('eventTemplate')->middleware(AuthorizeApiWrite::class.':templates:update')->name('event-templates.update');
    Route::post('/field-templates', [FieldTemplateController::class, 'store'])->middleware(AuthorizeApiWrite::class.':templates:create')->name('field-templates.store');
    Route::put('/field-templates/{fieldTemplate}', [FieldTemplateController::class, 'update'])->whereNumber('fieldTemplate')->middleware(AuthorizeApiWrite::class.':templates:update')->name('field-templates.update');
});
