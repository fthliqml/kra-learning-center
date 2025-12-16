<?php

namespace App\Livewire\Components\EditCourse;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use App\Models\Competency;
use Mary\Traits\Toast;

class CourseInfo extends Component
{
    use WithFileUploads, Toast;

    public array $course = [
        'title' => '',
        'about' => '', // maps to description
        'group_comp' => '',
        'competency_id' => '',
        'thumbnail_url' => '',
    ];

    public ?int $courseId = null; // existing course id if editing

    protected string $originalHash = '';
    public bool $isDirty = false;
    public bool $hasEverSaved = false; // indicates at least one successful persist
    public bool $persisted = false; // reflects last known DB sync state

    public array $competencyOptions = [];

    public $thumbnail;

    protected $rules = [
        'course.title' => 'required|string|min:3',
        'course.about' => 'required|string|min:5',
        'course.group_comp' => 'required|string',
        'course.competency_id' => 'required|integer|exists:competency,id',
        'thumbnail' => 'nullable|image|max:2048',
    ];

    protected $messages = [
        'course.title.required' => 'Course title is required.',
        'course.title.min' => 'Course title must be at least :min characters.',
        'course.about.required' => 'About course is required.',
        'course.about.min' => 'About course must be at least :min characters.',
        'course.group_comp.required' => 'Group competency is required.',
        'course.competency_id.required' => 'Competency is required.',
        'course.competency_id.integer' => 'Competency is invalid.',
        'course.competency_id.exists' => 'Selected competency is invalid.',
        'thumbnail.image' => 'Thumbnail must be an image file.',
        'thumbnail.max' => 'Thumbnail size may not be greater than 2MB.',
    ];

    public function updatedThumbnail(): void
    {
        if ($this->thumbnail) {
            $this->validateOnly('thumbnail');
            $path = $this->thumbnail->store('thumbnails', 'public');
            $this->course['thumbnail_url'] = $path;
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
            'competency_id' => $this->course['competency_id'] ?: null,
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
            if ($model = Course::with('competency')->find($this->courseId)) {
                $this->course = [
                    'title' => $model->title,
                    'about' => $model->description,
                    'group_comp' => $model->competency->type ?? '',
                    'competency_id' => $model->competency_id,
                    'thumbnail_url' => $model->thumbnail_url,
                ];
                $this->hasEverSaved = true;
                $this->persisted = true;
                // Load competency options after setting group_comp
                $this->loadCompetencyOptions();
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

    public function getGroupOptionsProperty()
    {
        return Competency::select('type')
            ->distinct()
            ->orderBy('type')
            ->get()
            ->map(fn($c) => ['value' => $c->type, 'label' => $c->type])
            ->toArray();
    }

    public function loadCompetencyOptions(): void
    {
        $group = $this->course['group_comp'] ?? '';

        if (!$group) {
            $this->competencyOptions = [];
            return;
        }

        $this->competencyOptions = Competency::where('type', $group)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ])
            ->toArray();
    }

    /** Search method for x-choices searchable competency dropdown */
    public function search(string $value = ''): void
    {
        $group = $this->course['group_comp'] ?? '';

        if (!$group) {
            $this->competencyOptions = [];
            return;
        }

        $query = Competency::where('type', $group);

        // Apply search filter if value provided
        if (trim($value) !== '') {
            $query->where('name', 'like', '%' . $value . '%');
        }

        $this->competencyOptions = $query
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ])
            ->toArray();
    }

    public function updated($name): void
    {
        // Track all changes to course properties or thumbnail
        if (str_starts_with($name, 'course.') || $name === 'thumbnail' || str_starts_with($name, 'competencyOptions')) {
            $this->computeDirty();
        }
    }

    public function updatedCourse(): void
    {
        // Catch any course array changes
        $this->computeDirty();
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
        $this->course['competency_id'] = '';
        $this->loadCompetencyOptions();
        $this->computeDirty();
    }

    public function updatedCourseCompetencyId(): void
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

    public function placeholder()
    {
        return view('components.skeletons.course-info');
    }
}
