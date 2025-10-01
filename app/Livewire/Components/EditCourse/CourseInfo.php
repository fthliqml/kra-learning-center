<?php

namespace App\Livewire\Components\EditCourse;

use Livewire\Component;
use Livewire\WithFileUploads;

class CourseInfo extends Component
{
    use WithFileUploads;

    public array $course = [
        'title' => '',
        'about' => '',
        'group_comp' => '',
        'thumbnail_url' => '',
    ];

    protected string $originalHash = '';
    public bool $isDirty = false;
    public bool $hasEverSaved = false; // indicates at least one successful persist
    public bool $persisted = false; // reflects last known DB sync state

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
        'course.about' => 'required|string|min:5',
        'course.group_comp' => 'required|string',
        'thumbnail' => 'nullable|image|max:2048',
    ];

    protected $messages = [
        'course.title.required' => 'Course title is required.',
        'course.title.min' => 'Course title must be at least :min characters.',
        'course.about.required' => 'About course is required.',
        'course.about.min' => 'About course must be at least :min characters.',
        'course.group_comp.required' => 'Group competency is required.',
        'thumbnail.image' => 'Thumbnail must be an image file.',
        'thumbnail.max' => 'Thumbnail size may not be greater than 2MB.',
    ];

    public function updatedThumbnail(): void
    {
        if ($this->thumbnail) {
            // Validate only the thumbnail when it changes
            $this->validateOnly('thumbnail');
            $path = $this->thumbnail->store('thumbnails', 'public');
            $this->course['thumbnail_url'] = asset('storage/' . $path);
            $this->computeDirty();
        }
    }

    public function saveDraft(): void
    {
        $this->validate();
        // Persist minimal data (stub) - replace with actual model logic later
        // Example: Course::updateOrCreate(['id' => $this->courseId], [...$this->course])
        $this->dispatch('notify', type: 'success', message: 'Draft saved');
        $this->snapshot();
        $this->hasEverSaved = true;
        $this->persisted = true;
    }

    public function saveAndNext(): void
    {
        $this->validate();
        dump($this->course);
        // TODO: persist full data (e.g. Course::updateOrCreate(...)) including thumbnail_url
        $this->dispatch('setTab', 'pretest');
        $this->snapshot();
        $this->hasEverSaved = true;
        $this->persisted = true;
    }

    public function mount(): void
    {
        $this->snapshot();
    }

    protected function snapshot(): void
    {
        $this->originalHash = $this->hashState();
        $this->isDirty = false;
        // after snapshot we assume local state == persisted state only if persist flag set externally
        if (!$this->hasEverSaved) {
            $this->persisted = false; // never saved yet
        }
    }

    protected function hashState(): string
    {
        return md5(json_encode($this->course));
    }

    public function updated($name): void
    {
        if (str_starts_with($name, 'course.') || $name === 'thumbnail') {
            $this->computeDirty();
        }
    }

    public function updatedCourseTitle(): void
    {
        $this->computeDirty();
    }
    public function updatedCourseAbout(): void
    {
        $this->computeDirty();
    }
    public function updatedCourseGroupComp(): void
    {
        $this->computeDirty();
    }

    protected function computeDirty(): void
    {
        $this->isDirty = $this->hashState() !== $this->originalHash;
        if ($this->isDirty) {
            $this->persisted = false; // diverged from persisted snapshot
        }
    }

    public function render()
    {
        return view('components.edit-course.course-info');
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
