<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DatabaseAnalysisController;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/report-view/{id}', function ($id) {
    return view('report', ['id' => $id]);
});

