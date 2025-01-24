<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\FieldTemplateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/user', function (Request $request) { return $request->user();});

    Route::apiResource('events', EventController::class);
    Route::apiResource('field-templates', FieldTemplateController::class);
});
