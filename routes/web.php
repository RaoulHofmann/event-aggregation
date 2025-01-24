<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
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

    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('events.index');
        Route::get('/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/', [EventController::class, 'store'])->name('events.store');
        Route::get('/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    });

    Route::prefix('event-templates')->group(function () {
        Route::get('/', [EventTemplateController::class, 'index'])->name('event-templates.index');
        Route::get('/create', [EventTemplateController::class, 'create'])->name('event-templates.create');
        Route::post('/', [EventTemplateController::class, 'store'])->name('event-templates.store');
        Route::get('/{eventTemplate}/edit', [EventTemplateController::class, 'edit'])->name('event-templates.edit');
        Route::put('/{eventTemplate}', [EventTemplateController::class, 'update'])->name('event-templates.update');
        Route::delete('/{eventTemplate}', [EventTemplateController::class, 'destroy'])->name('event-templates.destroy');
    });

    Route::prefix('field-templates')->group(function () {
        Route::get('/', [FieldTemplateController::class, 'index'])->name('field-templates.index');
        Route::get('/create', [FieldTemplateController::class, 'create'])->name('field-templates.create');
        Route::post('/', [FieldTemplateController::class, 'store'])->name('field-templates.store');
        Route::get('/{fieldTemplate}/edit', [FieldTemplateController::class, 'edit'])->name('field-templates.edit');
        Route::put('/{fieldTemplate}', [FieldTemplateController::class, 'update'])->name('field-templates.update');
        Route::delete('/{fieldTemplate}', [FieldTemplateController::class, 'destroy'])->name('field-templates.destroy');
    });


});
