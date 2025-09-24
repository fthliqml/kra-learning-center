<?php

use App\Http\Controllers\Training\TrainingModuleController;
use App\Livewire\Pages\Courses\Courses;
use App\Livewire\Pages\Training\DataTrainer;
use App\Livewire\Pages\Training\Module;
use App\Livewire\Pages\Training\Schedule;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.dashboard');
});

Route::get('/training/module', Module::class)->name('training-module.index');
Route::post('/training/module', [TrainingModuleController::class, 'store'])->name('training-module.store');
Route::put('/training/module/{id}', [TrainingModuleController::class, 'edit'])->name('training-module.edit');
Route::delete('/training/module/{id}', [TrainingModuleController::class, 'destroy'])->name('training-module.destroy');

Route::get('/training/schedule', Schedule::class)->name('training-schedule.index');

Route::get('/training/trainer', DataTrainer::class)->name('data-trainer.index');

Route::get('/courses', Courses::class)->name('courses.index');
