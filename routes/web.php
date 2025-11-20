<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Pages\Certification\CertificationApproval;
use App\Livewire\Pages\Certification\CertificationPoint;
use App\Livewire\Pages\Certification\CertificationModule;
use App\Livewire\Pages\Certification\CertificationSchedule;
use App\Livewire\Pages\Courses\Courses;
use App\Livewire\Pages\Courses\Overview;
use App\Livewire\Pages\Courses\Pretest;
use App\Livewire\Pages\Courses\Posttest;
use App\Livewire\Pages\Courses\Result;
use App\Livewire\Pages\Courses\ModulePage;
use App\Livewire\Pages\Courses\SectionQuiz;
use App\Livewire\Pages\EditCourse\CoursesManagement;
use App\Livewire\Pages\EditCourse\EditCourse;
use App\Livewire\Pages\Survey\SurveyEmployee;
use App\Livewire\Pages\Survey\SurveyManagement;
use App\Livewire\Pages\Survey\SurveyManagementDetail;
use App\Livewire\Pages\Survey\SurveyPreview;
use App\Livewire\Pages\Survey\TakeSurvey;
use App\Livewire\Pages\SurveyTemplate\EditSurveyTemplate;
use App\Livewire\Pages\SurveyTemplate\SurveyTemplate;
use App\Livewire\Pages\Certification\CertificationHistory;
use App\Livewire\Pages\Training\DataTrainer;
use App\Livewire\Pages\Training\History;
use App\Livewire\Pages\Training\Module;
use App\Livewire\Pages\Training\Request;
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
    Route::get('/training/request', Request::class)->name('training-request.index');
    Route::get('/training/trainer', DataTrainer::class)->name('data-trainer.index');
    Route::get('/training/history', History::class)->name('training-history.index');


    // Courses
    Route::get('/courses', Courses::class)->name('courses.index');
    Route::get('/courses/{course}/overview', Overview::class)->name('courses-overview.show');
    Route::get('/courses/{course}/pretest', Pretest::class)->name('courses-pretest.index');
    Route::get('/courses/{course}/modules', ModulePage::class)->name('courses-modules.index');
    Route::get('/courses/{course}/sections/{section}/quiz', SectionQuiz::class)->name('courses-section-quiz.show');
    Route::get('/courses/{course}/posttest', Posttest::class)->name('courses-posttest.index');
    Route::get('/courses/{course}/result', Result::class)->name('courses-result.index');
    Route::get('/courses/management', CoursesManagement::class)->name('courses-management.index');
    Route::get('/courses/{course}/edit', EditCourse::class)->name('edit-course.index');
    Route::get('/courses/add', EditCourse::class)->name('add-course.index');

    // Survey
    Route::get('/survey/{level}', SurveyEmployee::class)->name('survey.index');
    Route::get('/survey/{level}/take/{surveyId}', TakeSurvey::class)->name('survey.take');
    Route::get('/survey/{level}/preview/{surveyId}', SurveyPreview::class)->name('survey.preview');
    Route::get('/survey/{level}/management', SurveyManagement::class)->name('survey-management.index');
    Route::get('/survey/{level}/edit/{surveyId}', SurveyManagementDetail::class)->name('survey.edit');

    // Survey Templates
    Route::get('/survey-template', SurveyTemplate::class)->name('survey-template.index');
    Route::get('/survey-template/{level}/edit/{surveyId}', EditSurveyTemplate::class)->name('survey-template.edit');

    // Certification
    Route::get('/certification/module', CertificationModule::class)->name('certification-module.index');
    Route::get('/certification/schedule', CertificationSchedule::class)->name('certification-schedule.index');
    Route::get('/certification/point', CertificationPoint::class)->name('certification-point.index');
    Route::get('/certification/approval', CertificationApproval::class)->name('certification-approval.index');
    Route::get('/certification/history', CertificationHistory::class)->name('certification-history.index');
});
