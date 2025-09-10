<?php

use App\Http\Controllers\TrainingModuleController;
use App\Livewire\Pages\Training\Module;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.dashboard');
});

Route::get('/training/module', Module::class)->name('training-module.index');
Route::post('/training/module', [TrainingModuleController::class, 'store'])->name('training-module.store');
Route::put('/training/module/{id}', [TrainingModuleController::class, 'edit'])->name('training-module.edit');
Route::delete('/training/module/{id}', [TrainingModuleController::class, 'destroy'])->name('training-module.destroy');


