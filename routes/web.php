<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFieldController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/events', [EventController::class, 'index'])->name('events');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::get('/event-fields', [EventFieldController::class, 'index'])->name('event-fields');
    Route::get('/event-fields/create', [EventFieldController::class, 'create'])->name('event-fields.create');
    Route::post('/event-fields', [EventFieldController::class, 'store'])->name('event-fields.store');
    Route::get('/event-fields/{eventField}/edit', [EventFieldController::class, 'edit'])->name('event-fields.edit');
    Route::put('/event-fields/{eventField}', [EventFieldController::class, 'update'])->name('event-fields.update');
    Route::delete('/event-fields/{eventField}', [EventFieldController::class, 'destroy'])->name('event-fields.destroy');

});
