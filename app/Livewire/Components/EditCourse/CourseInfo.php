<?php

namespace App\Livewire\Components\EditCourse;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use Mary\Traits\Toast;

class CourseInfo extends Component
{
    use WithFileUploads, Toast;

    public array $course = [
        'title' => '',
        'about' => '', // maps to description
        'group_comp' => '',
        'thumbnail_url' => '',
    ];

    public ?int $courseId = null; // existing course id if editing

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

        // Map local state to DB fields
        $payload = [
            'title' => $this->course['title'],
            'description' => $this->course['about'],
            'group_comp' => $this->course['group_comp'],
            'thumbnail_url' => $this->course['thumbnail_url'] ?: '',
            'status' => 'draft',
        ];

        $course = Course::updateOrCreate(
            ['id' => $this->courseId],
            $payload
        );

        if (!$this->courseId) {
            $this->courseId = $course->id;
        }

        $this->success('Successfully updated course info!', position: 'toast-top toast-center');


        $this->snapshot();
        $this->hasEverSaved = true;
        $this->persisted = true;

        // Broadcast courseCreated so other components (e.g., Pretest) can latch onto the ID
        if ($this->courseId) {
            // Dispatch with positional argument so listener method signature (int $newCourseId) receives it correctly
            $this->dispatch('courseCreated', $this->courseId);
        }
    }

    /**
     * Handle clicking the "Next" button from the Course Info tab.
     * Simplified: only switch tab without auto-save, events, or toasts.
     */
    public function goNext(): void
    {
        $this->dispatch('setTab', 'pretest');
    }

    public function goManagement(): mixed
    {
        // Full HTTP redirect (refresh) to courses management page
        return redirect()->route('courses-management.index');
    }

    public function mount(): void
    {
        // If editing existing course, hydrate
        if ($this->courseId) {
            if ($model = Course::find($this->courseId)) {
                $this->course = [
                    'title' => $model->title,
                    'about' => $model->description,
                    'group_comp' => $model->group_comp,
                    'thumbnail_url' => $model->thumbnail_url,
                ];
                $this->hasEverSaved = true;
                $this->persisted = true;
            }
        }
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
