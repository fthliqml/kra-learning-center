<?php

namespace App\Livewire\Pages\Courses;

use Livewire\Component;
use Livewire\WithFileUploads;

class EditCourse extends Component
{
    use WithFileUploads;

    /**
     * Active tab slug.
     */
    public string $activeTab = 'course-info';

    /**
     * Parent only coordinates active tab; heavy data moved to child components (hybrid pattern).
     */
    protected $listeners = [
        'setTab' => 'setTab', // children can request tab change
    ];

    public function mount(): void
    {
    }

    public function goNextTab(string $to): void
    {
        $this->activeTab = $to;
    }

    public function setTab(string $to): void
    {
        $this->activeTab = $to;
    }

    public function render()
    {
        return view('pages.courses.edit-course');
    }
}

