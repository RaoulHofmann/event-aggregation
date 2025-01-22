<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFieldController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('events', EventController::class)->middleware('auth:sanctum');
Route::apiResource('event-fields', EventFieldController::class)->middleware('auth:sanctum');
