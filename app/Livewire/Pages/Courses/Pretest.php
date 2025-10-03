<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Pretest extends Component
{
    public Course $course;
    public ?Test $pretest = null;
    public array $questions = [];

    public function mount(Course $course)
    {
        $userId = Auth::id();
        $assigned = $course->userCourses()->where('user_id', $userId)->exists();
        if (!$assigned) {
            abort(403, 'You are not assigned to this course.');
        }

        // Eager load learning modules for sidebar listing
        $this->course = $course->load(['learningModules' => fn($q) => $q->orderBy('id')]);

        // Load pretest with questions & options once
        $this->pretest = Test::with(['questions.options'])
            ->where('course_id', $this->course->id)
            ->where('type', 'pretest')
            ->first();

        if ($this->pretest) {
            $collection = $this->pretest->questions->map(function ($q) {
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
            });

            // Randomize once if flag set
            if ($this->pretest->randomize_question) {
                $collection = $collection->shuffle();
            }

            $this->questions = $collection->values()->all();
        }
    }

    public function render()
    {
        return view('pages.courses.pretest', [
            'course' => $this->course,
            'pretest' => $this->pretest,
            'questions' => $this->questions,
        ])->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'pretest',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
        ]);
    }
}
