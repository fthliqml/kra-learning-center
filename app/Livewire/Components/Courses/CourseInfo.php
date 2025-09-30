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

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="p-6 space-y-6 animate-pulse">
            <div class="h-7 w-60 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
                <div class="space-y-3">
                    <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-10 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
            <div class="space-y-3">
                <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-40 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="flex gap-3">
                <div class="h-10 w-36 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-10 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>
        HTML;
    }
}
