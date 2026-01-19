<?php

namespace App\Livewire\Components\Training\TrainingForm\Traits;

use App\Models\Competency;
use App\Models\Course;
use App\Models\Trainer;
use App\Models\TrainingModule;
use App\Models\User;

/**
 * Trait TrainingFormDropdowns
 * 
 * Handles dropdown options loading and search functionality.
 * Extracted from TrainingFormModal for better separation of concerns.
 */
trait TrainingFormDropdowns
{
    // ===== DROPDOWN OPTIONS =====
    public array $courseOptions = [];
    public array $trainingModuleOptions = [];
    public array $competencyOptions = [];
    
    // ===== SEARCHABLE COLLECTIONS =====
    public $usersSearchable;
    public $trainersSearchable;
    
    // ===== LOADING FLAGS =====
    public bool $dataLoaded = false;
    
    // ===== CACHES =====
    private array $courseGroupCache = [];
    private array $competencyGroupCache = [];
    private array $competencyNameCache = [];

    /**
     * Initialize searchable collections
     */
    protected function initSearchables(): void
    {
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
    }

    /**
     * Lazy load all dropdown data - called only when modal opens.
     * PUBLIC: Called from Alpine.js via $wire.loadDropdownData()
     */
    public function loadDropdownData(): void
    {
        if ($this->dataLoaded) {
            return;
        }

        $this->loadCourseOptions();
        $this->loadTrainingModuleOptions();
        $this->loadCompetencyOptions();
        $this->userSearch();
        $this->trainerSearch();
        
        $this->dataLoaded = true;
    }

    /**
     * Load course options with caching.
     */
    protected function loadCourseOptions(): void
    {
        $this->courseOptions = cache()->remember('training_form_course_options', 3600, function () {
            return Course::with('competency:id,type')
                ->select('id', 'title', 'competency_id')
                ->orderBy('title')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id, 
                    'title' => $c->title, 
                    'group_comp' => $c->competency->type ?? null
                ])
                ->toArray();
        });
    }

    /**
     * Load training module options with caching.
     */
    protected function loadTrainingModuleOptions(): void
    {
        $this->trainingModuleOptions = cache()->remember('training_form_module_options', 3600, function () {
            return TrainingModule::with('competency')
                ->orderBy('title')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'group_comp' => $m->competency?->type ?? null
                ])
                ->toArray();
        });
    }

    /**
     * Load competency options with caching.
     */
    protected function loadCompetencyOptions(?int $ensureCompetencyId = null): void
    {
        $options = cache()->remember('training_form_competency_options', 3600, function () {
            return Competency::query()
                ->select('id', 'code', 'name')
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
                ->toArray();
        });

        // Ensure selected competency is in list (for edit mode)
        if ($ensureCompetencyId) {
            $existingIds = array_column($options, 'id');
            if (!in_array($ensureCompetencyId, $existingIds, true)) {
                $selected = Competency::query()
                    ->select('id', 'code', 'name')
                    ->find($ensureCompetencyId);

                if ($selected) {
                    array_unshift($options, [
                        'id' => $selected->id,
                        'name' => trim($selected->code . ' - ' . $selected->name),
                    ]);
                }
            }
        }

        $this->competencyOptions = $options;
    }

    /**
     * Search users for participant selection.
     */
    public function userSearch(string $value = ''): void
    {
        $selectedOptions = collect([]);
        if (!empty($this->participants) && $this->participants !== ['']) {
            $selectedOptions = User::whereIn('id', $this->participants)->get();
        }

        $searchResults = User::where('name', 'like', "%{$value}%")
            ->limit(10)
            ->get();

        $this->usersSearchable = $searchResults->merge($selectedOptions)
            ->unique('id')
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);
    }

    /**
     * Search trainers for trainer selection.
     */
    public function trainerSearch(string $value = ''): void
    {
        $selected = collect([]);
        if (!empty($this->trainerId)) {
            $selected = Trainer::with('user')
                ->where('id', $this->trainerId)
                ->get();
        }

        // PERFORMANCE: If no search value, load limited trainers (not ALL)
        if (empty($value)) {
            $results = cache()->remember('training_form_trainers_list', 3600, function () {
                return Trainer::with('user')
                    ->limit(20)
                    ->get()
                    ->map(fn($trainer) => [
                        'id' => $trainer->id,
                        'name' => $trainer->name ?: ($trainer->user?->name ?? 'Unknown'),
                    ])
                    ->toArray();
            });
            
            $merged = collect($results);
            if ($selected->isNotEmpty()) {
                $selectedMapped = $selected->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name ?: ($t->user?->name ?? 'Unknown'),
                ]);
                $merged = $merged->concat($selectedMapped)->unique('id');
            }
            
            $this->trainersSearchable = $merged;
            return;
        }

        // Search with value
        $results = Trainer::with('user')
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$value}%"));
            })
            ->limit(10)
            ->get();

        $this->trainersSearchable = $results->merge($selected)
            ->unique('id')
            ->map(fn($trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name ?: ($trainer->user?->name ?? 'Unknown'),
            ]);
    }

    /**
     * Search courses.
     */
    public function searchCourse(string $value = ''): void
    {
        if (empty($value)) {
            $this->loadCourseOptions();
            return;
        }

        $this->courseOptions = Course::with('competency:id,type')
            ->select('id', 'title', 'competency_id')
            ->where('title', 'like', "%{$value}%")
            ->orderBy('title')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'group_comp' => $c->competency->type ?? null])
            ->toArray();
    }

    /**
     * Search training modules.
     */
    public function searchTrainingModule(string $value = ''): void
    {
        if (empty($value)) {
            $this->loadTrainingModuleOptions();
            return;
        }

        $this->trainingModuleOptions = TrainingModule::with('competency')
            ->where('title', 'like', "%{$value}%")
            ->orderBy('title')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'group_comp' => $m->competency?->type ?? null
            ])
            ->toArray();
    }

    /**
     * Search competencies.
     */
    public function searchCompetency(string $value = ''): void
    {
        if (empty($value)) {
            $this->loadCompetencyOptions($this->parseCompetencyIdFromValue($this->competency_id));
            return;
        }

        $options = Competency::query()
            ->select('id', 'code', 'name')
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
            ->toArray();

        // Ensure current selection remains visible
        $selectedId = $this->parseCompetencyIdFromValue($this->competency_id);
        if ($selectedId) {
            $existingIds = array_column($options, 'id');
            if (!in_array($selectedId, $existingIds, true)) {
                $selected = Competency::query()
                    ->select('id', 'code', 'name')
                    ->find($selectedId);
                if ($selected) {
                    array_unshift($options, [
                        'id' => $selected->id,
                        'name' => trim($selected->code . ' - ' . $selected->name),
                    ]);
                }
            }
        }

        $this->competencyOptions = $options;
    }

    /**
     * Get group_comp from course ID with caching.
     */
    protected function getCourseGroupComp(?int $courseId): ?string
    {
        if (!$courseId) {
            return null;
        }

        if (isset($this->courseGroupCache[$courseId])) {
            return $this->courseGroupCache[$courseId];
        }

        $course = Course::with('competency:id,type')
            ->select('id', 'competency_id')
            ->find($courseId);

        $group = trim((string) ($course?->competency?->type ?? ''));
        $result = $group !== '' ? $group : null;
        
        $this->courseGroupCache[$courseId] = $result;
        
        return $result;
    }

    /**
     * Get group_comp from competency ID with caching.
     */
    protected function getCompetencyGroupComp(?int $competencyId): ?string
    {
        if (!$competencyId) {
            return null;
        }

        if (isset($this->competencyGroupCache[$competencyId])) {
            return $this->competencyGroupCache[$competencyId];
        }

        $competency = Competency::query()
            ->select('id', 'type')
            ->find($competencyId);

        $group = trim((string) ($competency?->type ?? ''));
        $result = $group !== '' ? $group : null;
        
        $this->competencyGroupCache[$competencyId] = $result;
        
        return $result;
    }

    /**
     * Get training name from competency ID with caching.
     */
    protected function getCompetencyTrainingName(?int $competencyId): ?string
    {
        if (!$competencyId) {
            return null;
        }

        if (isset($this->competencyNameCache[$competencyId])) {
            return $this->competencyNameCache[$competencyId];
        }

        $competency = Competency::query()
            ->select('id', 'name')
            ->find($competencyId);

        $name = trim((string) ($competency?->name ?? ''));
        $result = $name !== '' ? $name : null;
        
        $this->competencyNameCache[$competencyId] = $result;
        
        return $result;
    }

    /**
     * Parse competency ID from various value formats.
     */
    protected function parseCompetencyIdFromValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        // Option object/array from select components
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : (array) $value;
            $candidate = $arr['id'] ?? ($arr['value'] ?? null);
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }

            // Sometimes only label is provided (e.g. "CODE - Name")
            $label = $arr['name'] ?? ($arr['label'] ?? null);
            if (is_string($label)) {
                return $this->parseCompetencyIdFromValue($label);
            }
            return null;
        }

        // Scalar
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        // Try to extract code from "CODE - Name"
        $code = null;
        if (str_contains($raw, ' - ')) {
            $code = trim(explode(' - ', $raw, 2)[0]);
        }
        if ($code) {
            $id = Competency::query()->where('code', $code)->value('id');
            return $id ? (int) $id : null;
        }

        return null;
    }

    /**
     * Parse competency name from value.
     */
    protected function parseCompetencyNameFromValue(mixed $value): ?string
    {
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : (array) $value;
            $value = $arr['name'] ?? ($arr['label'] ?? null);
        }
        if (!is_string($value)) {
            return null;
        }
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, ' - ')) {
            $name = trim(explode(' - ', $raw, 2)[1] ?? '');
            return $name !== '' ? $name : null;
        }
        return null;
    }
}
