<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTemplateController;
use App\Http\Controllers\FieldTemplateController;
use App\Http\Middleware\SetPostgresSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    SetPostgresSchema::class
])->group(function () {
    Route::get('/user', function (Request $request) { return $request->user();});
    Route::get('/event-templates', [EventTemplateController::class, 'get']);
    Route::get('/field-templates', [FieldTemplateController::class, 'get']);
    Route::get('/events', [EventController::class, 'get']);
});
