<?php

use App\Http\Controllers\TrainingModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.dashboard');
});

Route::get('/training/module', [TrainingModuleController::class, 'index']);
