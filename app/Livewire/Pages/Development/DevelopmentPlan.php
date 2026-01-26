<?php

namespace App\Livewire\Pages\Development;

use App\Models\Competency;
use App\Models\TrainingModule;
use App\Models\TrainingPlanRecom;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use App\Models\SelfLearningPlan;
use App\Models\Training;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\User;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;

class DevelopmentPlan extends Component
{
    use Toast;

    private ?array $recommendedCompetencyIdsCache = null;
    private ?array $recommendedCompetencyIdsByTypeCache = null;

    public function updatedSelectedYear(): void
    {
        $this->recommendedCompetencyIdsCache = null;
        $this->recommendedCompetencyIdsByTypeCache = null;
    }

    // Modal states
    public $addModal = false;
    public $activeTab = 'training';

    // Edit mode
    public $isEdit = false;
    public $editingCategory = null; // Track which category is being edited

    // Filter by year
    public $selectedYear;

    // Training Plan form (multiple rows)
    public $trainingPlans = [
        ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null],
        ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null],
        ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null],
    ];

    public $trainingPlanKindOptions = [
        ['value' => 'competency', 'label' => 'Competency'],
        ['value' => 'module', 'label' => 'Training Module'],
    ];

    // Self Learning Plans (multiple)
    public $selfLearningPlans = [
        ['id' => null, 'title' => '', 'objective' => '', 'start_date' => '', 'end_date' => '', 'status' => null],
    ];

    // Mentoring Plans (multiple)
    public $mentoringPlans = [
        [
            'id' => null,
            'mentor_id' => '',
            'objective' => '',
            'method' => '',
            'frequency' => 2,
            'duration' => '',
            'plan_months' => ['', ''],
            'status' => null,
        ],
    ];

    // Project Plans (multiple)
    public $projectPlans = [
        ['id' => null, 'name' => '', 'objective' => '', 'mentor_id' => '', 'status' => null],
    ];

    // Legacy single form properties (for backward compatibility)
    public $selfLearning = [
        'title' => '',
        'objective' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    public $mentoring = [
        'mentor_id' => '',
        'objective' => '',
        'method' => '',
        'frequency' => '',
        'duration' => '',
    ];

    public $project = [
        'name' => '',
        'objective' => '',
        'mentor_id' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    // Options
    public $typeOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $methodOptions = [
        ['value' => 'online', 'label' => 'Online'],
        ['value' => 'offline', 'label' => 'Offline'],
        ['value' => 'hybrid', 'label' => 'Hybrid'],
    ];

    public function mount()
    {
        // Default to current year (as string for datepicker compatibility)
        $this->selectedYear = (string) now()->year;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openAddModal($category = 'training')
    {
        if ($category === 'training' && !$this->hasCompetencyRecommendations()) {
            $this->error('Training Plan belum bisa diisi karena belum ada rekomendasi untuk tahun ' . $this->selectedYear . '.', position: 'toast-top toast-center');
            return;
        }

        $this->resetForm();
        $this->isEdit = false;
        $this->editingCategory = $category; // Set editing category for add mode
        // Set active tab based on category
        $this->activeTab = match ($category) {
            'self_learning' => 'self-learning',
            'mentoring' => 'mentoring',
            'project' => 'project',
            default => 'training',
        };
        $this->addModal = true;
    }

    public function openEditModal($category = 'training')
    {
        if ($category === 'training' && !$this->hasCompetencyRecommendations()) {
            $this->error('Training Plan belum bisa diedit karena belum ada rekomendasi untuk tahun ' . $this->selectedYear . '.', position: 'toast-top toast-center');
            return;
        }

        $this->resetForm();
        $this->isEdit = true;
        $this->editingCategory = $category; // Track which category is being edited
        // Set active tab based on category
        $this->activeTab = match ($category) {
            'self_learning' => 'self-learning',
            'mentoring' => 'mentoring',
            'project' => 'project',
            default => 'training',
        };
        $this->loadExistingPlans();
        $this->addModal = true;
    }

    private function loadExistingPlans()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        // Load Training Plans
        $trainingPlans = TrainingPlan::with(['competency', 'trainingModule.competency', 'approver'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->trainingPlans = [];
        foreach ($trainingPlans as $plan) {
            $competencyType = (string) ($plan->trainingModule?->competency?->type ?? $plan->competency?->type ?? '');
            // Build plan_id: "competency:X" or "module:X"
            $planId = $plan->training_module_id
                ? 'module:' . $plan->training_module_id
                : 'competency:' . $plan->competency_id;

            $this->trainingPlans[] = [
                'id' => $plan->id,
                'group' => $competencyType,
                'plan_id' => $planId,
                'status' => $plan->status,
            ];
        }
        // Cap to 3 rows in UI
        if (count($this->trainingPlans) > 3) {
            $this->trainingPlans = array_slice($this->trainingPlans, 0, 3);
        }

        // Ensure exactly 3 rows for UI (pad with empty rows)
        while (count($this->trainingPlans) < 3) {
            $this->trainingPlans[] = ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null];
        }

        // Load Self Learning Plans
        $selfLearningPlans = SelfLearningPlan::where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->selfLearningPlans = [];
        foreach ($selfLearningPlans as $plan) {
            $this->selfLearningPlans[] = [
                'id' => $plan->id,
                'title' => $plan->title,
                'objective' => $plan->objective,
                'start_date' => $plan->start_date?->format('Y-m-d'),
                'end_date' => $plan->end_date?->format('Y-m-d'),
                'status' => $plan->status,
            ];
        }
        // Add empty row if empty
        if (empty($this->selfLearningPlans)) {
            $this->selfLearningPlans[] = [
                'id' => null,
                'title' => '',
                'objective' => '',
                'start_date' => '',
                'end_date' => '',
                'status' => null
            ];
        }

        // Load Mentoring Plans
        $mentoringPlans = MentoringPlan::where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->mentoringPlans = [];
        foreach ($mentoringPlans as $plan) {
            $months = $plan->plan_months ?? [];
            // Ensure at least 2 rows for UI
            while (count($months) < 2) {
                $months[] = '';
            }

            $this->mentoringPlans[] = [
                'id' => $plan->id,
                'mentor_id' => $plan->mentor_id,
                'objective' => $plan->objective,
                'method' => $plan->method,
                'frequency' => $plan->frequency,
                'duration' => $plan->duration,
                'plan_months' => $months,
                'status' => $plan->status,
            ];
        }
        if (empty($this->mentoringPlans)) {
            $this->mentoringPlans[] = [
                'id' => null,
                'mentor_id' => '',
                'objective' => '',
                'method' => '',
                'frequency' => 2,
                'duration' => '',
                'plan_months' => ['', ''],
                'status' => null
            ];
        }

        // Load Project Plans
        $projectPlans = ProjectPlan::where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->projectPlans = [];
        foreach ($projectPlans as $plan) {
            $this->projectPlans[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'objective' => $plan->objective,
                'mentor_id' => $plan->mentor_id,
                'status' => $plan->status,
            ];
        }
        if (empty($this->projectPlans)) {
            $this->projectPlans[] = [
                'id' => null,
                'name' => '',
                'objective' => '',
                'mentor_id' => '',
                'status' => null
            ];
        }
    }

    public function closeAddModal()
    {
        $this->addModal = false;
        $this->resetForm();
    }

    // Add new row methods
    public function addTrainingRow()
    {
        if (count($this->trainingPlans) >= 3) {
            $this->error('Maksimal 3 Training Plan.', position: 'toast-top toast-center');
            return;
        }

        $this->trainingPlans[] = [
            'id' => null,
            'group' => '',
            'plan_id' => '',
            'status' => null,
        ];
    }

    public function addSelfLearningRow()
    {
        $this->selfLearningPlans[] = ['id' => null, 'title' => '', 'objective' => '', 'start_date' => '', 'end_date' => '', 'status' => null];
    }

    public function addMentoringRow()
    {
        $this->mentoringPlans[] = [
            'id' => null,
            'mentor_id' => '',
            'objective' => '',
            'method' => '',
            'frequency' => 2,
            'duration' => '',
            'plan_months' => ['', ''],
            'status' => null,
        ];
    }

    public function addProjectRow()
    {
        $this->projectPlans[] = ['id' => null, 'name' => '', 'objective' => '', 'mentor_id' => '', 'status' => null];
    }

    // Remove row methods
    public function removeTrainingRow($index)
    {
        if (count($this->trainingPlans) > 1) {
            $plan = $this->trainingPlans[$index];
            // If it has an ID, delete from database
            if (!empty($plan['id'])) {
                $existing = TrainingPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->trainingPlans[$index]);
            $this->trainingPlans = array_values($this->trainingPlans);
        }
    }

    public function removeSelfLearningRow($index)
    {
        if (count($this->selfLearningPlans) > 1) {
            $plan = $this->selfLearningPlans[$index];
            if (!empty($plan['id'])) {
                $existing = SelfLearningPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->selfLearningPlans[$index]);
            $this->selfLearningPlans = array_values($this->selfLearningPlans);
        }
    }

    public function removeMentoringRow($index)
    {
        if (count($this->mentoringPlans) > 1) {
            $plan = $this->mentoringPlans[$index];
            if (!empty($plan['id'])) {
                $existing = MentoringPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->mentoringPlans[$index]);
            $this->mentoringPlans = array_values($this->mentoringPlans);
        }
    }

    public function removeProjectRow($index)
    {
        if (count($this->projectPlans) > 1) {
            $plan = $this->projectPlans[$index];
            if (!empty($plan['id'])) {
                $existing = ProjectPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->projectPlans[$index]);
            $this->projectPlans = array_values($this->projectPlans);
        }
    }

    // Delete methods
    public function deleteTrainingPlan($id)
    {
        $plan = TrainingPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Training plan deleted successfully', position: 'toast-top toast-center');
    }

    public function deleteSelfLearningPlan($id)
    {
        $plan = SelfLearningPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Self learning plan deleted successfully', position: 'toast-top toast-center');
    }

    public function deleteMentoringPlan($id)
    {
        $plan = MentoringPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Mentoring plan deleted successfully', position: 'toast-top toast-center');
    }

    public function deleteProjectPlan($id)
    {
        $plan = ProjectPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Project plan deleted successfully', position: 'toast-top toast-center');
    }

    public function resetForm()
    {
        $this->activeTab = 'training';
        $this->isEdit = false;
        $this->trainingPlans = [
            ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null],
            ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null],
            ['id' => null, 'group' => '', 'plan_id' => '', 'status' => null],
        ];
        $this->selfLearningPlans = [
            ['id' => null, 'title' => '', 'objective' => '', 'start_date' => '', 'end_date' => '', 'status' => null],
        ];
        $this->mentoringPlans = [
            [
                'id' => null,
                'mentor_id' => '',
                'objective' => '',
                'method' => '',
                'frequency' => 2,
                'duration' => '',
                'plan_months' => ['', ''],
                'status' => null,
            ],
        ];
        $this->projectPlans = [
            ['id' => null, 'name' => '', 'objective' => '', 'mentor_id' => '', 'status' => null],
        ];
        // Legacy
        $this->selfLearning = [
            'title' => '',
            'objective' => '',
            'start_date' => '',
            'end_date' => '',
        ];
        $this->mentoring = [
            'mentor_id' => '',
            'objective' => '',
            'method' => '',
            'frequency' => 2,
            'duration' => '',
        ];
        $this->project = [
            'name' => '',
            'objective' => '',
            'mentor_id' => '',
            'start_date' => '',
            'end_date' => '',
        ];
    }

    public function updatedTrainingPlans($value, $name)
    {
        // $name examples: "0.group", "1.plan_id" (Livewire strips property name prefix)
        if (!is_string($name)) {
            return;
        }

        $parts = explode('.', $name);
        if (count($parts) < 2) {
            return;
        }

        $index = (int) $parts[0];
        $field = (string) $parts[1];

        if (!isset($this->trainingPlans[$index])) {
            return;
        }

        if ($field === 'group') {
            // Changing group should reset plan selection.
            $this->trainingPlans[$index]['plan_id'] = '';
        }
    }

    public function getRecommendedTrainingModulesOptions(): array
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        if (!$userId || !$year) {
            return [];
        }

        $moduleIds = TrainingPlanRecom::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('is_active', true)
            ->whereNotNull('training_module_id')
            ->pluck('training_module_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($moduleIds)) {
            return [];
        }

        return TrainingModule::query()
            ->with('competency')
            ->whereIn('id', $moduleIds)
            ->orderBy('title')
            ->get()
            ->map(function (TrainingModule $module) {
                $type = trim((string) ($module->competency?->type ?? ''));
                $label = trim(($type ? $type . ' - ' : '') . (string) $module->title);

                return [
                    'value' => (int) $module->id,
                    'label' => $label !== '' ? $label : ('Module #' . $module->id),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get recommended group options (only groups that have recommendations from admin).
     */
    public function getRecommendedGroupOptions(): array
    {
        $byType = $this->getRecommendedCompetencyIdsByTypeForSelectedYear();
        $moduleGroups = $this->getRecommendedModuleGroupsForSelectedYear();

        // Merge competency groups and module groups
        $allGroups = array_unique(array_merge(array_keys($byType), $moduleGroups));

        if (empty($allGroups)) {
            return [];
        }

        sort($allGroups);

        return collect($allGroups)
            ->map(fn($group) => ['value' => $group, 'label' => $group])
            ->values()
            ->all();
    }

    /**
     * Get groups from recommended training modules.
     */
    private function getRecommendedModuleGroupsForSelectedYear(): array
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        if (!$userId || !$year) {
            return [];
        }

        $moduleIds = TrainingPlanRecom::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('is_active', true)
            ->whereNotNull('training_module_id')
            ->pluck('training_module_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($moduleIds)) {
            return [];
        }

        return TrainingModule::query()
            ->with('competency')
            ->whereIn('id', $moduleIds)
            ->get()
            ->map(fn($m) => trim((string) ($m->competency?->type ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get combined training plan options (competencies + modules) for a group.
     */
    public function getTrainingPlanOptions(string $group): array
    {
        if (empty($group)) {
            return [];
        }

        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        // Combine recommended competencies + modules.
        // Hide competency options that are already covered by a recommended module's competency (avoid duplicates).
        $moduleIds = TrainingPlanRecom::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('is_active', true)
            ->whereNotNull('training_module_id')
            ->pluck('training_module_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $options = [];

        $modules = collect();
        $moduleCompetencyIds = [];

        if (!empty($moduleIds)) {
            $modules = TrainingModule::query()
                ->with('competency')
                ->whereIn('id', $moduleIds)
                ->whereHas('competency', fn($q) => $q->where('type', $group))
                ->orderBy('title')
                ->get();

            $moduleCompetencyIds = $modules
                ->pluck('competency_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $recommendedCompetencyIds = $this->getRecommendedCompetencyIdsForType($group);
        if (!empty($recommendedCompetencyIds)) {
            $competenciesQuery = Competency::query()
                ->whereIn('id', $recommendedCompetencyIds)
                ->where('type', $group);

            if (!empty($moduleCompetencyIds)) {
                $competenciesQuery->whereNotIn('id', $moduleCompetencyIds);
            }

            $competencies = $competenciesQuery
                ->orderBy('name')
                ->get();

            foreach ($competencies as $comp) {
                $options[] = [
                    'value' => 'competency:' . $comp->id,
                    'label' => $comp->name,
                ];
            }
        }

        if ($modules->isNotEmpty()) {
            foreach ($modules as $module) {
                $options[] = [
                    'value' => 'module:' . $module->id,
                    'label' => '[Module] ' . $module->title,
                ];
            }
        }

        return $options;
    }

    public function updatedMentoringPlans($value, $name)
    {
        if (strpos($name, '.frequency') === false) {
            return;
        }

        $parts = explode('.', $name);

        if (count($parts) < 3) {
            return;
        }

        $index = (int) $parts[1];

        if (!isset($this->mentoringPlans[$index])) {
            return;
        }

        $rawFrequency = $this->mentoringPlans[$index]['frequency'] ?? 0;
        $frequency = (int) $rawFrequency;

        if ($frequency < 2) {
            $frequency = 2;
            $this->mentoringPlans[$index]['frequency'] = $frequency;
        }

        $months = $this->mentoringPlans[$index]['plan_months'] ?? [];
        $currentCount = count($months);

        if ($currentCount < $frequency) {
            for ($i = $currentCount; $i < $frequency; $i++) {
                $months[] = '';
            }
        } elseif ($currentCount > $frequency) {
            $months = array_slice($months, 0, $frequency);
        }

        $this->mentoringPlans[$index]['plan_months'] = $months;
    }

    public function getCompetenciesByType($type)
    {
        if (empty($type)) {
            return [];
        }

        // Hard-block: if there are no recommendations at all for the selected year,
        // user cannot pick any competency for Training Plan.
        if (!$this->hasCompetencyRecommendations()) {
            return [];
        }

        $query = Competency::query()->where('type', $type);

        $recommendedIdsForType = $this->getRecommendedCompetencyIdsForType((string) $type);
        if (!empty($recommendedIdsForType)) {
            $query->whereIn('id', $recommendedIdsForType);
        }

        return $query
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
            ->toArray();
    }

    public function hasCompetencyRecommendations(): bool
    {
        return !empty($this->getRecommendedCompetencyIdsForSelectedYear());
    }

    private function getRecommendedCompetencyIdsForType(string $type): array
    {
        $byType = $this->getRecommendedCompetencyIdsByTypeForSelectedYear();

        $type = trim((string) $type);
        if ($type === '') {
            return [];
        }

        return $byType[$type] ?? [];
    }

    private function getRecommendedCompetencyIdsByTypeForSelectedYear(): array
    {
        if ($this->recommendedCompetencyIdsByTypeCache !== null) {
            return $this->recommendedCompetencyIdsByTypeCache;
        }

        $ids = $this->getRecommendedCompetencyIdsForSelectedYear();
        if (empty($ids)) {
            $this->recommendedCompetencyIdsByTypeCache = [];
            return $this->recommendedCompetencyIdsByTypeCache;
        }

        $map = [];
        $rows = Competency::query()
            ->whereIn('id', $ids)
            ->get(['id', 'type']);

        foreach ($rows as $row) {
            $type = trim((string) ($row->type ?? ''));
            if ($type === '') {
                continue;
            }

            $map[$type] ??= [];
            $map[$type][] = (int) $row->id;
        }

        foreach ($map as $t => $typeIds) {
            $map[$t] = array_values(array_unique(array_filter(array_map('intval', $typeIds))));
        }

        $this->recommendedCompetencyIdsByTypeCache = $map;
        return $this->recommendedCompetencyIdsByTypeCache;
    }

    private function getRecommendedCompetencyIdsForSelectedYear(): array
    {
        if ($this->recommendedCompetencyIdsCache !== null) {
            return $this->recommendedCompetencyIdsCache;
        }

        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        if (!$userId || !$year) {
            $this->recommendedCompetencyIdsCache = [];
            return $this->recommendedCompetencyIdsCache;
        }

        $baseQuery = TrainingPlanRecom::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('is_active', true);

        $directCompetencyIds = (clone $baseQuery)
            ->whereNotNull('competency_id')
            ->pluck('competency_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        $recommendedModuleIds = (clone $baseQuery)
            ->whereNotNull('training_module_id')
            ->pluck('training_module_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $moduleCompetencyIds = empty($recommendedModuleIds)
            ? []
            : TrainingModule::query()
            ->whereIn('id', $recommendedModuleIds)
            ->pluck('competency_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        $this->recommendedCompetencyIdsCache = collect(array_merge($directCompetencyIds, $moduleCompetencyIds))
            ->unique()
            ->values()
            ->all();

        return $this->recommendedCompetencyIdsCache;
    }

    public function save()
    {
        $user = Auth::user();

        try {
            // If editing a specific category, only save that category
            if ($this->editingCategory) {
                match ($this->editingCategory) {
                    'training' => $this->saveTrainingPlans($user),
                    'self_learning' => $this->saveSelfLearningPlan($user),
                    'mentoring' => $this->saveMentoringPlan($user),
                    'project' => $this->saveProjectPlan($user),
                };
            } else {
                // Save all tabs at once when adding new plans
                $this->saveTrainingPlans($user);
                $this->saveSelfLearningPlan($user);
                $this->saveMentoringPlan($user);
                $this->saveProjectPlan($user);
            }

            $this->closeAddModal();
            $this->success('Development plan saved successfully!', position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Failed to save: ' . $e->getMessage(), position: 'toast-top toast-center');
        }
    }

    public function saveDraft()
    {
        $user = Auth::user();

        try {
            // If editing a specific category, only save that category as draft
            if ($this->editingCategory) {
                match ($this->editingCategory) {
                    'training' => $this->saveTrainingPlans($user, true),
                    'self_learning' => $this->saveSelfLearningPlan($user, true),
                    'mentoring' => $this->saveMentoringPlan($user, true),
                    'project' => $this->saveProjectPlan($user, true),
                };
            } else {
                // Save all tabs at once as draft when adding new plans
                $this->saveTrainingPlans($user, true);
                $this->saveSelfLearningPlan($user, true);
                $this->saveMentoringPlan($user, true);
                $this->saveProjectPlan($user, true);
            }

            $this->closeAddModal();
            $this->success('Development plan draft saved successfully!', position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Failed to save draft: ' . $e->getMessage(), position: 'toast-top toast-center');
        }
    }

    /**
     * Determine initial status for a plan when submitted (non-draft).
     *
     * Rules:
     * - If saving as draft  -> always 'draft'.
     * - Level 1 approver routing (non-LID):
     *   - employee submits:
     *     - if any SPV exists in area -> pending_spv
     *     - else if any Section Head exists in area -> pending_section_head
     *     - else if any Dept Head exists in area -> pending_dept_head
     *     - else -> pending_lid
     *   - SPV submits:
     *     - if any Section Head exists in area -> pending_section_head
     *     - else if any Dept Head exists in area -> pending_dept_head
     *     - else -> pending_lid
     *   - Section Head submits:
     *     - if any Dept Head exists in area -> pending_dept_head
     *     - else -> pending_lid
     *   - Dept Head submits:
     *     - skip level 1 -> pending_lid
     * - LID section submitters route to pending_lid.
     */
    private function determineInitialStatus(User $user, bool $isDraft = false): string
    {
        if ($isDraft) {
            return 'draft';
        }

        $position = strtolower(trim($user->position ?? ''));
        $section = $user->section ? strtolower(trim($user->section)) : null;

        $isLidSection = $section === 'lid';

        // LID section submitters go directly to LID approval
        if ($isLidSection) {
            return 'pending_lid';
        }

        $hasSpvInArea = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
            ->when($user->section, fn($q) => $q->where('section', $user->section))
            ->when(!$user->section && $user->department, fn($q) => $q->where('department', $user->department))
            ->when(!$user->section && !$user->department, fn($q) => $q->whereRaw('1 = 0'))
            ->exists();

        $hasSectionHeadInArea = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
            ->when($user->section, fn($q) => $q->where('section', $user->section))
            ->when(!$user->section && $user->department, fn($q) => $q->where('department', $user->department))
            ->when(!$user->section && !$user->department, fn($q) => $q->whereRaw('1 = 0'))
            ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
            ->exists();

        $hasDeptHeadInArea = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
            ->when($user->department, fn($q) => $q->where('department', $user->department))
            ->when(!$user->department, fn($q) => $q->whereRaw('1 = 0'))
            ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
            ->exists();

        // Dept Head submits: skip level 1 and route to LID
        if ($position === 'department_head') {
            return 'pending_lid';
        }

        // Section Head submits: approve by Dept Head if exists
        if ($position === 'section_head') {
            return $hasDeptHeadInArea ? 'pending_dept_head' : 'pending_lid';
        }

        // SPV submits: Section Head (area) if exists, else Dept Head
        if ($position === 'supervisor') {
            if ($hasSectionHeadInArea) {
                return 'pending_section_head';
            }
            if ($hasDeptHeadInArea) {
                return 'pending_dept_head';
            }
            return 'pending_lid';
        }

        // Employee submits: SPV -> Section Head -> Dept Head -> LID
        if ($hasSpvInArea) {
            return 'pending_spv';
        }
        if ($hasSectionHeadInArea) {
            return 'pending_section_head';
        }
        if ($hasDeptHeadInArea) {
            return 'pending_dept_head';
        }

        return 'pending_lid';
    }

    private function saveTrainingPlans($user, $isDraft = false)
    {
        $status = $this->determineInitialStatus($user, $isDraft);

        // Hard-block: if no recommendations exist for the selected year, Training Plan cannot be filled.
        if (!$this->hasCompetencyRecommendations()) {
            throw new \Exception('Training Plan belum bisa diisi karena belum ada rekomendasi untuk tahun ' . $this->selectedYear . '.');
        }

        $recommendedModuleIds = TrainingPlanRecom::query()
            ->where('user_id', $user->id)
            ->where('year', (int) $this->selectedYear)
            ->where('is_active', true)
            ->whereNotNull('training_module_id')
            ->pluck('training_module_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($this->trainingPlans as $plan) {
            // Skip empty rows
            if (empty($plan['plan_id'])) {
                continue;
            }

            // Parse plan_id format: "competency:123" or "module:456"
            $planId = (string) $plan['plan_id'];
            $parts = explode(':', $planId, 2);
            $kind = $parts[0] ?? '';
            $id = isset($parts[1]) ? (int) $parts[1] : 0;

            if (!in_array($kind, ['competency', 'module'], true) || $id <= 0) {
                continue;
            }

            $competencyId = null;
            $moduleId = null;

            // Module-based plan
            if ($kind === 'module') {
                if (!in_array($id, $recommendedModuleIds, true)) {
                    throw new \Exception('Selected training module is not in the recommended list for this year.');
                }

                $module = TrainingModule::query()->whereKey($id)->first(['id', 'competency_id']);
                if (!$module || !$module->competency_id) {
                    throw new \Exception('Invalid training module selected.');
                }

                $competencyId = (int) $module->competency_id;
                $moduleId = $id;
            }

            // Competency-based plan
            if ($kind === 'competency') {
                $competencyId = $id;

                $competencyType = (string) (Competency::query()->whereKey($competencyId)->value('type') ?? '');
                $recommendedIdsForType = $this->getRecommendedCompetencyIdsForType($competencyType);
                if (!empty($recommendedIdsForType) && !in_array($competencyId, $recommendedIdsForType, true)) {
                    throw new \Exception('Selected competency is not in the recommended list for this group/year.');
                }

                $moduleId = null;
            }

            // Save or update
            if (!empty($plan['id'])) {
                $existingPlan = TrainingPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update([
                        'competency_id' => $competencyId,
                        'training_module_id' => $moduleId,
                        'status' => $status,
                        'year' => (int) $this->selectedYear,
                    ]);
                }
            } else {
                TrainingPlan::create([
                    'user_id' => $user->id,
                    'competency_id' => $competencyId,
                    'training_module_id' => $moduleId,
                    'status' => $status,
                    'year' => (int) $this->selectedYear,
                ]);
            }
        }
    }

    private function saveSelfLearningPlan($user, $isDraft = false)
    {
        $status = $this->determineInitialStatus($user, $isDraft);

        foreach ($this->selfLearningPlans as $plan) {
            // Skip empty rows
            if (empty($plan['title']) && empty($plan['objective'])) {
                continue;
            }

            $data = [
                'title' => $plan['title'] ?: 'Draft',
                'objective' => $plan['objective'] ?: '',
                'start_date' => $plan['start_date'] ?: now(),
                'end_date' => $plan['end_date'] ?: now(),
                'status' => $status,
                'year' => (int) $this->selectedYear,
            ];

            if (!empty($plan['id'])) {
                // Update existing
                $existingPlan = SelfLearningPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update($data);
                }
            } else {
                // Create new
                SelfLearningPlan::create(array_merge(['user_id' => $user->id], $data));
            }
        }
    }

    private function saveMentoringPlan($user, $isDraft = false)
    {
        $status = $this->determineInitialStatus($user, $isDraft);

        // First pass: validate and normalize planned months
        $normalizedPlans = [];

        foreach ($this->mentoringPlans as $plan) {
            // Skip empty rows
            if (empty($plan['objective']) && empty($plan['mentor_id'])) {
                continue;
            }

            $months = $plan['plan_months'] ?? [];
            $months = array_values(array_filter($months, fn($m) => !empty($m)));

            if (!$isDraft && count($months) < 2) {
                throw new \Exception('Each mentoring plan must have at least 2 planned months.');
            }

            $plan['plan_months'] = $months;
            $normalizedPlans[] = $plan;
        }

        // Second pass: save to database
        foreach ($normalizedPlans as $plan) {
            $data = [
                'mentor_id' => $plan['mentor_id'] ?: null,
                'objective' => $plan['objective'] ?: '',
                'method' => $plan['method'] ?: '',
                'frequency' => $plan['frequency'] ?: 0,
                'duration' => $plan['duration'] ?: 0,
                'plan_months' => $plan['plan_months'] ?: null,
                'status' => $status,
                'year' => (int) $this->selectedYear,
            ];

            if (!empty($plan['id'])) {
                $existingPlan = MentoringPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update($data);
                }
            } else {
                MentoringPlan::create(array_merge(['user_id' => $user->id], $data));
            }
        }
    }

    public function addMentoringMonth($planIndex)
    {
        if (!isset($this->mentoringPlans[$planIndex]['plan_months'])) {
            $this->mentoringPlans[$planIndex]['plan_months'] = ['', ''];
        }

        $this->mentoringPlans[$planIndex]['plan_months'][] = '';

        $months = $this->mentoringPlans[$planIndex]['plan_months'];
        $this->mentoringPlans[$planIndex]['frequency'] = max(2, count($months));
    }

    public function removeMentoringMonth($planIndex, $monthIndex)
    {
        if (!isset($this->mentoringPlans[$planIndex]['plan_months'])) {
            return;
        }

        $months = $this->mentoringPlans[$planIndex]['plan_months'];

        if (count($months) <= 2) {
            return; // minimal dua bulan
        }

        unset($months[$monthIndex]);
        $months = array_values($months);
        $this->mentoringPlans[$planIndex]['plan_months'] = $months;
        $this->mentoringPlans[$planIndex]['frequency'] = max(2, count($months));
    }

    private function saveProjectPlan($user, $isDraft = false)
    {
        $status = $this->determineInitialStatus($user, $isDraft);

        foreach ($this->projectPlans as $plan) {
            // Skip empty rows
            if (empty($plan['name']) && empty($plan['objective'])) {
                continue;
            }

            $data = [
                'mentor_id' => $plan['mentor_id'] ?: null,
                'name' => $plan['name'] ?: 'Draft',
                'objective' => $plan['objective'] ?: '',
                'status' => $status,
                'year' => (int) $this->selectedYear,
            ];

            if (!empty($plan['id'])) {
                $existingPlan = ProjectPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update($data);
                }
            } else {
                ProjectPlan::create(array_merge(['user_id' => $user->id], $data));
            }
        }
    }

    /**
     * Check if user can edit plans
     * User can edit plans that are in draft or rejected status
     */
    public function getCanEditProperty()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        // Check if user has any editable plans (draft or rejected)
        $hasEditableTraining = TrainingPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();

        $hasEditableSelfLearning = SelfLearningPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();

        $hasEditableMentoring = MentoringPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();

        $hasEditableProject = ProjectPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();

        return $hasEditableTraining || $hasEditableSelfLearning || $hasEditableMentoring || $hasEditableProject;
    }

    /**
     * Check if user can edit Training Plans specifically
     */
    public function getCanEditTrainingProperty()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        return TrainingPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();
    }

    /**
     * Check if user can edit Self Learning Plans specifically
     */
    public function getCanEditSelfLearningProperty()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        return SelfLearningPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();
    }

    /**
     * Check if user can edit Mentoring Plans specifically
     */
    public function getCanEditMentoringProperty()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        return MentoringPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();
    }

    /**
     * Check if user can edit Project Plans specifically
     */
    public function getCanEditProjectProperty()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        return ProjectPlan::where('user_id', $userId)
            ->where('year', $year)
            ->where(function ($q) {
                $q->where('status', 'draft')
                    ->orWhere('status', 'like', 'rejected%');
            })
            ->exists();
    }

    /**
     * Check if user can add new plans
     * User can add plans if it's not yet submitted or if all plans are rejected
     */
    public function getCanAddPlanProperty()
    {
        // User can always add plans (business rule may vary)
        return true;
    }

    public function render()
    {
        $user = Auth::user();

        // Get mentors (users with supervisor or leadership positions in user's organizational hierarchy)
        $mentors = User::whereIn('position', ['supervisor', 'section_head', 'department_head', 'division_head'])
            ->where(function ($query) use ($user) {
                // Same section (supervisor or section head)
                $query->where(function ($q) use ($user) {
                    $q->whereIn('position', ['supervisor', 'section_head'])
                        ->where('section', $user->section);
                })
                    // Same department (department head)
                    ->orWhere(function ($q) use ($user) {
                        $q->where('position', 'department_head')
                            ->where('department', $user->department);
                    })
                    // Same division (division head)
                    ->orWhere(function ($q) use ($user) {
                        $q->where('position', 'division_head')
                            ->where('division', $user->division);
                    });
            })
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name . ' (' . ucfirst(str_replace('_', ' ', $u->position)) . ')'])
            ->toArray();

        // Parse year from datepicker (handles both "2025" and full date strings)
        $selectedYearInt = (int) $this->selectedYear;

        // Get user's development plans for selected year
        $trainingPlansData = TrainingPlan::with(['competency', 'trainingModule.competency', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $selfLearningData = SelfLearningPlan::with(['approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $mentoringData = MentoringPlan::with(['mentor', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $projectData = ProjectPlan::with(['mentor', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $recommendedCompetencies = TrainingPlanRecom::query()
            ->with('competency')
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->where('is_active', true)
            ->get();

        // Statistics for selected year
        $trainingPlanCount = $trainingPlansData->count();
        $selfLearningCount = $selfLearningData->count();
        $mentoringCount = $mentoringData->count();
        $projectCount = $projectData->count();
        $totalPlans = $trainingPlanCount + $selfLearningCount + $mentoringCount + $projectCount;

        // Training Realization: count training plans that already have a closed training
        // (done/approved/rejected). This matches the realization badge semantics.
        $trainingRealized = 0;
        foreach ($trainingPlansData as $plan) {
            if ($plan->hasClosedTraining()) {
                $trainingRealized++;
            }
        }

        // Self Learning, Mentoring, Project realization (based on approved status for now)
        // TODO: Implement actual realization tracking when features are available
        $selfLearningRealized = $selfLearningData->where('status', 'approved')->count();
        $mentoringRealized = $mentoringData->where('status', 'approved')->count();
        $projectRealized = $projectData->where('status', 'approved')->count();

        // Chart data
        $chartData = [
            $trainingPlanCount,
            $selfLearningCount,
            $mentoringCount,
            $projectCount,
        ];

        return view('pages.development.development-plan', [
            'user' => $user,
            'mentors' => $mentors,
            'recommendedCompetencies' => $recommendedCompetencies,
            'trainingPlanCount' => $trainingPlanCount,
            'selfLearningCount' => $selfLearningCount,
            'mentoringCount' => $mentoringCount,
            'projectCount' => $projectCount,
            'totalPlans' => $totalPlans,
            'trainingPlansData' => $trainingPlansData,
            'selfLearningData' => $selfLearningData,
            'mentoringData' => $mentoringData,
            'projectData' => $projectData,
            'chartData' => $chartData,
            'trainingRealized' => $trainingRealized,
            'selfLearningRealized' => $selfLearningRealized,
            'mentoringRealized' => $mentoringRealized,
            'projectRealized' => $projectRealized,
            'canEdit' => $this->canEdit,
            'canAddPlan' => $this->canAddPlan,
            'canEditTraining' => $this->canEditTraining,
            'canEditSelfLearning' => $this->canEditSelfLearning,
            'canEditMentoring' => $this->canEditMentoring,
            'canEditProject' => $this->canEditProject,
        ]);
    }
}
