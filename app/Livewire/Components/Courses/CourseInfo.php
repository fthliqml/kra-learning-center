<?php

namespace App\Livewire\Components\Courses;

use Livewire\Component;
use Livewire\WithFileUploads;

class CourseInfo extends Component
{
    use WithFileUploads;

    public array $course = [
        'title' => '',
        'about' => '',
        'group_comp' => '',
    ];

    public array $groupOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $thumbnail;

    protected $rules = [
        'course.title' => 'required|string|min:3',
        'course.about' => 'nullable|string',
        'course.group_comp' => 'nullable|string',
        'thumbnail' => 'nullable|image|max:2048',
    ];

    public function saveAndNext(): void
    {
        $this->validate();
        // TODO: persist (e.g. Course::updateOrCreate(...))
        $this->dispatch('setTab', 'pretest');
    }

    public function render()
    {
        return view('components.courses.course-info');
    }
}
