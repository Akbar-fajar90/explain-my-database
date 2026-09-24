<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DatabaseAnalysisController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/analyze', [DatabaseAnalysisController::class, 'analyze']);
Route::get('/erd/{id}', [DatabaseAnalysisController::class, 'erd']);
Route::get('/report/{id}', [DatabaseAnalysisController::class, 'report']);
