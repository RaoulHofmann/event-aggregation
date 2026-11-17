<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
use App\Http\Controllers\IntegrationController;
use App\Http\Middleware\SetPostgresSchema;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/designs/homepage', fn () => Inertia::render('Designs/Homepage'))->name('designs.homepage');

Route::get('/', fn () => Inertia::render('Welcome', ['canLogin' => Route::has('login'), 'canRegister' => Route::has('register')]));
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', SetPostgresSchema::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/events/preview', [EventController::class, 'preview'])->name('events.preview');
    Route::post('/events/{event}/exceptions', [EventController::class, 'exception'])->name('events.exceptions');
    Route::resource('events', EventController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('event-templates', EventTemplateController::class)->parameters(['event-templates' => 'eventTemplate'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('field-templates', FieldTemplateController::class)->parameters(['field-templates' => 'fieldTemplate'])->only(['index', 'store', 'update', 'destroy']);
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
    Route::put('/integrations/{connection}', [IntegrationController::class, 'update'])->name('integrations.update');
    Route::post('/integrations/{connection}/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
    Route::delete('/integrations/{connection}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');
});
