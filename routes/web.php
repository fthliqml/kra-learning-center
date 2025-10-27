<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Pages\Courses\Courses;
use App\Livewire\Pages\Courses\Overview;
use App\Livewire\Pages\Courses\Pretest;
use App\Livewire\Pages\Courses\ModulePage;
use App\Livewire\Pages\EditCourse\CoursesManagement;
use App\Livewire\Pages\EditCourse\EditCourse;
use App\Livewire\Pages\Survey\EditSurvey;
use App\Livewire\Pages\Survey\SurveyEmployee;
use App\Livewire\Pages\Survey\SurveyManagement;
use App\Livewire\Pages\Survey\TakeSurvey;
use App\Livewire\Pages\SurveyTemplate\SurveyTemplate;
use App\Livewire\Pages\Training\DataTrainer;
use App\Livewire\Pages\Training\Module;
use App\Livewire\Pages\Training\Schedule;
use Illuminate\Support\Facades\Route;

// Public (guest) routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected (auth) routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Training
    Route::get('/training/module', Module::class)->name('training-module.index');
    Route::get('/training/schedule', Schedule::class)->name('training-schedule.index');
    Route::get('/training/trainer', DataTrainer::class)->name('data-trainer.index');

    // Courses
    Route::get('/courses', Courses::class)->name('courses.index');
    Route::get('/courses/{course}/overview', Overview::class)->name('courses-overview.show');
    Route::get('/courses/{course}/pretest', Pretest::class)->name('courses-pretest.index');
    Route::get('/courses/{course}/modules', ModulePage::class)->name('courses-modules.index');
    Route::get('/courses/management', CoursesManagement::class)->name('courses-management.index');
    Route::get('/courses/{course}/edit', EditCourse::class)->name('edit-course.index');
    Route::get('/courses/add', EditCourse::class)->name('add-course.index');

    // Survey
    Route::get('/survey/{level}', SurveyEmployee::class)->name('survey.index');
    Route::get('/survey/{level}/take/{surveyId}', TakeSurvey::class)->name('survey.take');
    Route::get('/survey/{level}/management', SurveyManagement::class)->name('survey-management.index');
    Route::get('/survey/{level}/edit/{surveyId}', EditSurvey::class)->name('survey.edit');

    // Survey Templates
    Route::get('/survey-template', SurveyTemplate::class)->name('survey-template.index');
});
