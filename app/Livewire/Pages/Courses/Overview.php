<?php

namespace App\Livewire\Pages\Courses;

use Livewire\Component;
use App\Models\Course;

class Overview extends Component
{
    public Course $course;
    public $modules;
    public int $modulesCount = 0;
    public int $assignUsers = 0;
    public int $durationDays = 0;

    public function mount(Course $course)
    {
        $this->course = $course->load([
            'learningModules' => function ($q) {
                $q->orderBy('id');
            },
        ]);

        $this->modules = $this->course->learningModules;
        $this->modulesCount = $this->modules?->count() ?? 0;
        $this->assignUsers = $this->course->users()->count();
    }

    public function render()
    {
        return view('pages.courses.overview');
    }
}
