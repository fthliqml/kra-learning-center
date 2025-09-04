<?php

use App\Http\Controllers\TrainingHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.dashboard');
});

Route::get('/history/training', [TrainingHistoryController::class, 'index']);
