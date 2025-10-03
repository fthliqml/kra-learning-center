<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
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
        // Fetch pretest with eager loaded questions & options
        $pretest = Test::with(['questions.options'])
            ->where('course_id', $this->course->id)
            ->where('type', 'pretest')
            ->first();

        $questions = collect();
        if ($pretest) {
            $questions = $pretest->questions->map(function ($q) {
                return [
                    'id' => 'q' . $q->id,
                    'db_id' => $q->id,
                    'type' => $q->question_type,
                    'text' => $q->text,
                    'options' => $q->question_type === 'multiple'
                        ? $q->options->map(fn($o) => [
                            'id' => $o->id,
                            'text' => $o->text,
                        ])->values()->all()
                        : [],
                ];
            })->values();
        }

        return view('pages.courses.pretest', [
            'course' => $this->course,
            'pretest' => $pretest,
            'questions' => $questions,
        ])->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'pretest',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
        ]);
    }
}
