<?php

namespace App\Livewire\Components\Training\TrainingForm;

use App\Models\Competency;
use App\Models\Course;
use App\Models\TrainingModule;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Trait TrainingFormState
 * 
 * Shared state and methods for Training Form components.
 * Used by TrainingFormModal and child tab components.
 */
trait TrainingFormState
{
    // ===== MODE & IDENTITY =====
    public bool $isEdit = false;
    public ?int $trainingId = null;
    
    // ===== FORM FIELDS =====
    public string $training_name = '';
    public string $training_type = 'IN';
    public string $group_comp = 'BMC';
    public ?int $selected_module_id = null;
    public ?int $course_id = null;
    public $competency_id = null;
    public string $date = '';
    public string $start_time = '';
    public string $end_time = '';
    public ?int $trainerId = null;
    public array $room = ['name' => '', 'location' => ''];
    public array $participants = [];
    
    // ===== DROPDOWN OPTIONS =====
    public array $courseOptions = [];
    public array $trainingModuleOptions = [];
    public array $competencyOptions = [];
    
    // ===== SEARCHABLE COLLECTIONS =====
    public Collection $usersSearchable;
    public Collection $trainersSearchable;
    
    // ===== ORIGINAL TYPE (for edit mode) =====
    public ?string $originalTrainingType = null;
    
    // ===== TRAINING TYPE OPTIONS =====
    public array $trainingTypeOptions = [
        ['id' => 'IN', 'name' => 'In-House'],
        ['id' => 'OUT', 'name' => 'Out-House'],
        ['id' => 'LMS', 'name' => 'LMS'],
        ['id' => 'BLENDED', 'name' => 'Blended'],
    ];
    
    public array $groupCompOptions = [
        ['id' => 'BMC', 'name' => 'Basic Mandatory Competencies'],
        ['id' => 'FC', 'name' => 'Functional Competencies'],
        ['id' => 'LC', 'name' => 'Leadership Competencies'],
    ];

    /**
     * Initialize searchable collections
     */
    public function initSearchables(): void
    {
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
    }

    /**
     * Load all dropdown options (lazy loaded when modal opens)
     */
    public function loadDropdowns(): void
    {
        // Course options with competency type
        $this->courseOptions = Course::with('competency:id,type')
            ->select('id', 'title', 'competency_id')
            ->orderBy('title')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id, 
                'title' => $c->title, 
                'group_comp' => $c->competency->type ?? null
            ])
            ->toArray();
        
        // Training module options with competency type
        $this->trainingModuleOptions = TrainingModule::with('competency')
            ->orderBy('title')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'group_comp' => $m->competency?->type ?? null
            ])
            ->toArray();
        
        // Competency options
        $this->competencyOptions = Competency::query()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
            ->toArray();
    }
    
    /**
     * Load searchable trainer collection
     */
    public function loadTrainers(string $search = ''): void
    {
        $selected = collect([]);
        if (!empty($this->trainerId)) {
            $selected = Trainer::with('user')->where('id', $this->trainerId)->get();
        }

        $query = Trainer::with('user');
        
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            })->limit(10);
        }

        $this->trainersSearchable = $query->get()
            ->merge($selected)
            ->unique('id')
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name ?: ($t->user?->name ?? 'Unknown')]);
    }
    
    /**
     * Load searchable user collection
     */
    public function loadUsers(string $search = ''): void
    {
        $selectedOptions = collect([]);
        if (!empty($this->participants) && $this->participants !== ['']) {
            $selectedOptions = User::whereIn('id', $this->participants)->get();
        }

        $searchResults = User::where('name', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        $this->usersSearchable = $searchResults->merge($selectedOptions)
            ->unique('id')
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);
    }
    
    /**
     * Reset all form fields to defaults
     */
    public function resetFormState(): void
    {
        $this->isEdit = false;
        $this->trainingId = null;
        $this->training_name = '';
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->selected_module_id = null;
        $this->course_id = null;
        $this->competency_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->trainerId = null;
        $this->room = ['name' => '', 'location' => ''];
        $this->participants = [];
        $this->originalTrainingType = null;
    }
    
    /**
     * Get form data as array for saving
     */
    public function getFormData(): array
    {
        return [
            'training_name' => $this->training_name,
            'training_type' => $this->training_type,
            'group_comp' => $this->group_comp,
            'selected_module_id' => $this->selected_module_id,
            'course_id' => $this->course_id,
            'competency_id' => $this->competency_id,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'trainerId' => $this->trainerId,
            'room' => $this->room,
            'participants' => $this->participants,
        ];
    }
    
    /**
     * Get group_comp from course
     */
    protected function getCourseGroupComp(?int $courseId): ?string
    {
        if (!$courseId) return null;
        
        $course = collect($this->courseOptions)->firstWhere('id', $courseId);
        return $course['group_comp'] ?? null;
    }
    
    /**
     * Get group_comp from training module
     */
    protected function getModuleGroupComp(?int $moduleId): ?string
    {
        if (!$moduleId) return null;
        
        $module = collect($this->trainingModuleOptions)->firstWhere('id', $moduleId);
        return $module['group_comp'] ?? null;
    }
}
