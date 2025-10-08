<?php

namespace App\Livewire\Pages\EditCourse;

use App\Models\Course;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditCourse extends Component
{
    use WithFileUploads;
    public $course;
    public bool $isCreating = false; // true when no course id yet
    public ?int $courseId = null;    // scalar id persisted across re-renders

    /**
     * Active tab slug.
     */
    public string $activeTab = 'course-info';

    /**
     * Parent only coordinates active tab; heavy data moved to child components (hybrid pattern).
     */
    protected $listeners = [
        'setTab' => 'setTab',
        'courseCreated' => 'onCourseCreated', // emitted by CourseInfo child after first save
    ];

    public function mount(Course $course): void
    {
        if ($course && $course->exists) {
            $this->course = $course;
            $this->isCreating = false;
            $this->courseId = $course->id;
        } else {
            $this->course = $course; // possibly empty model instance
            $this->isCreating = true;
            $this->activeTab = 'course-info';
            $this->courseId = null;
        }
    }

    public function goNextTab(string $to): void
    {
        $this->activeTab = $to;
    }

    public function setTab(string $to): void
    {
        // Prevent navigating away during creation until course has an id
        if ($this->isCreating && ($this->course?->id ?? null) === null && $to !== 'course-info') {
            return;
        }
        $this->activeTab = $to;
    }

    public function onCourseCreated(int $newId): void
    {
        if ($this->isCreating) {
            // Reload a fresh model so further relations / attributes available
            $this->course = Course::find($newId) ?? $this->course;
            $this->courseId = $newId;
            $this->isCreating = false;
        }
    }

    public function render()
    {
        return view('pages.edit-course.edit-course');
    }
}

