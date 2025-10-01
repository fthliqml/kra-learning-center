<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Pretest extends Component
{
    public Course $course;

    public function mount(Course $course)
    {
        $userId = Auth::id();
        $assigned = $course->userCourses()->where('user_id', $userId)->exists();
        if (!$assigned) {
            abort(403, 'You are not assigned to this course.');
        }

        // Eager load learning modules for sidebar listing (training relation removed)
        $this->course = $course->load(['learningModules' => function ($q) {
            $q->orderBy('id');
        }]);
    }

    public function render()
    {
        return view('pages.courses.pretest', ['course' => $this->course])
            ->layout('layouts.livewire.course', [
                'courseTitle' => $this->course->title,
                'stage' => 'pretest',
                'progress' => $this->course->progressForUser(),
                'stages' => ['pretest', 'module', 'posttest', 'result'],
                'modules' => $this->course->learningModules,
            ]);
    }
}
