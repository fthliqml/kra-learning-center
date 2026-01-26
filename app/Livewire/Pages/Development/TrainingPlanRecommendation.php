<?php

namespace App\Livewire\Pages\Development;

use App\Models\Competency;
use App\Models\TrainingModule;
use App\Models\TrainingPlanRecom;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class TrainingPlanRecommendation extends Component
{
    use Toast;
    public $selectedYear;

    public $filterDepartment = '';
    public $filterSection = '';

    public $filterKey = 0;

    public $selectedUserId = '';
    public $selectedUser = null;

    // Recommendation form (single row)
    public $recommendationType = 'competency'; // competency | training_module
    public $group = '';
    public $selectedId = '';

    public $recommendations = [];

    public $typeOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $recommendationTypeOptions = [
        ['value' => 'competency', 'label' => 'Competency'],
        ['value' => 'training_module', 'label' => 'Training Module'],
    ];

    public function getTrainingModulesByType($type): array
    {
        if (empty($type)) {
            return [];
        }

        return TrainingModule::query()
            ->with('competency:id,type')
            ->whereHas('competency', fn($q) => $q->where('type', $type))
            ->orderBy('title')
            ->get()
            ->map(fn($m) => ['value' => $m->id, 'label' => $m->title])
            ->toArray();
    }

    public function updatedRecommendationType(): void
    {
        $this->selectedId = '';
    }

    public function updatedGroup(): void
    {
        $this->selectedId = '';
    }

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->selectedYear = (string) now()->year;
    }

    public function updatedFilterDepartment(): void
    {
        $this->filterSection = '';
        $this->filterKey++;
        $this->resetSelectedEmployee();
    }

    public function updatedFilterSection(): void
    {
        $this->filterKey++;
        $this->resetSelectedEmployee();
    }

    public function updatedSelectedYear(): void
    {
        // Reload recommendations for the selected employee when year changes.
        $this->loadSelectedEmployee();
    }

    public function updatedSelectedUserId($value): void
    {
        $this->selectedUserId = (string) $value;
        $this->loadSelectedEmployee();
    }

    private function normalizeKey(?string $value): string
    {
        $v = (string) $value;

        // Normalize NBSP (common from copy/paste/Excel)
        $v = str_replace("\xC2\xA0", ' ', $v);

        // Collapse any whitespace (tabs/newlines/multiple spaces)
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        $v = trim($v);

        return $v === '' ? '' : mb_strtolower($v);
    }


    public function clearSelectedEmployee(): void
    {
        $this->resetSelectedEmployee();
    }

    private function resetSelectedEmployee(): void
    {
        $this->selectedUserId = '';
        $this->selectedUser = null;
        $this->recommendations = [];

        $this->recommendationType = 'competency';
        $this->group = '';
        $this->selectedId = '';
    }

    private function loadSelectedEmployee(): void
    {
        $userId = (int) $this->selectedUserId;
        if ($userId <= 0) {
            $this->selectedUser = null;
            $this->recommendations = [];
            return;
        }

        $this->selectedUser = User::find($userId);
        if (!$this->selectedUser) {
            $this->resetSelectedEmployee();
            return;
        }

        $this->loadRecommendations();
    }

    public function getDepartmentsProperty(): array
    {
        $query = User::query()
            ->whereNotNull('department')
            ->where('department', '!=', '');

        $values = $query
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->map(fn($dept) => (string) $dept)
            ->values();

        $grouped = $values->groupBy(fn($dept) => $this->normalizeKey($dept))
            ->filter(fn($items, $key) => (string) $key !== '');

        return $grouped
            ->map(fn($items, $key) => ['id' => (string) $key, 'name' => trim((string) $items->first())])
            ->values()
            ->toArray();
    }

    public function getSectionsProperty(): array
    {
        $deptKey = (string) $this->filterDepartment;

        // Build department->sections relationship in PHP using normalizeKey() so it works
        // even when DB values contain trailing spaces / NBSP / inconsistent casing.
        $rows = User::query()
            ->select(['department', 'section'])
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->get();

        $filtered = $rows->filter(function ($row) use ($deptKey) {
            if ($deptKey === '') {
                return true;
            }

            return $this->normalizeKey((string) ($row->department ?? '')) === $deptKey;
        });

        $sections = $filtered
            ->pluck('section')
            ->map(fn($section) => (string) $section)
            ->values();

        return $sections
            ->groupBy(fn($section) => $this->normalizeKey($section))
            ->filter(fn($items, $key) => (string) $key !== '')
            ->map(fn($items, $key) => ['id' => (string) $key, 'name' => trim((string) $items->first())])
            ->values()
            ->toArray();
    }

    public function getEmployeeOptionsProperty(): array
    {
        $deptKey = (string) $this->filterDepartment;
        $sectionKey = (string) $this->filterSection;

        $query = User::query()->select(['id', 'name', 'nrp', 'email', 'department', 'section', 'position']);

        // Pull more than 200 then filter in PHP to keep dropdown responsive.
        $rows = $query
            ->orderBy('name')
            ->limit(3000)
            ->get();

        $filtered = $rows->filter(function (User $u) use ($deptKey, $sectionKey) {
            if ($deptKey !== '' && $this->normalizeKey((string) ($u->department ?? '')) !== $deptKey) {
                return false;
            }
            if ($sectionKey !== '' && $this->normalizeKey((string) ($u->section ?? '')) !== $sectionKey) {
                return false;
            }
            return true;
        })->take(200);

        return $filtered
            ->map(function (User $u) {
                $nrp = trim((string) ($u->nrp ?? ''));
                $name = trim((string) ($u->name ?? ''));

                return [
                    'value' => $u->id,
                    'label' => $nrp !== '' ? "({$nrp}) - {$name}" : $name,
                ];
            })
            ->values()
            ->toArray();
    }

    private function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasPosition('section_head') && strtolower(trim($user->section ?? '')) === 'lid') {
            return;
        }

        abort(403);
    }

    // Modal methods removed: recommendations are managed inline on the main page.

    private function loadRecommendations(): void
    {
        if (!$this->selectedUserId) {
            $this->recommendations = [];
            return;
        }

        $year = (int) $this->selectedYear;

        $rows = TrainingPlanRecom::query()
            ->with(['competency', 'recommender', 'trainingModule'])
            ->where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'type' => $r->competency->type ?? ($r->trainingModule?->competency?->type ?? '-'),
                'competency' => $r->competency->name ?? ($r->trainingModule?->competency?->name ?? '-'),
                'training_module' => $r->trainingModule->title ?? '-',
                'is_active' => (bool) $r->is_active,
                'recommender' => $r->recommender->name ?? '-',
            ]);

        $this->recommendations = $rows
            ->sort(function (array $a, array $b): int {
                $typeA = strtoupper((string) ($a['type'] ?? ''));
                $typeB = strtoupper((string) ($b['type'] ?? ''));
                if ($typeA !== $typeB) {
                    return $typeA <=> $typeB;
                }

                // Within same type: show active first.
                $activeA = (int) ($a['is_active'] ?? false);
                $activeB = (int) ($b['is_active'] ?? false);
                if ($activeA !== $activeB) {
                    return $activeB <=> $activeA;
                }

                $compA = mb_strtoupper((string) ($a['competency'] ?? ''));
                $compB = mb_strtoupper((string) ($b['competency'] ?? ''));
                if ($compA !== $compB) {
                    return $compA <=> $compB;
                }

                $modA = mb_strtoupper((string) ($a['training_module'] ?? ''));
                $modB = mb_strtoupper((string) ($b['training_module'] ?? ''));
                if ($modA !== $modB) {
                    return $modA <=> $modB;
                }

                // Deterministic fallback
                return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
            })
            ->values()
            ->toArray();
    }

    public function getCompetenciesByType($type): array
    {
        if (empty($type)) {
            return [];
        }

        return Competency::query()
            ->where('type', $type)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
            ->toArray();
    }

    public function addRecommendation(): void
    {
        $this->authorizeAccess();

        if (!$this->selectedUserId) {
            $this->error('Please select an employee first.', position: 'toast-top toast-center');
            return;
        }

        $this->validate([
            'selectedYear' => 'required',
            'recommendationType' => 'required|in:competency,training_module',
            'group' => 'required|string',
            'selectedId' => 'required|integer',
        ], [
            'selectedYear.required' => 'Year is required.',
            'recommendationType.required' => 'Type is required.',
            'recommendationType.in' => 'Type must be valid.',
            'group.required' => 'Group is required.',
            'group.string' => 'Group is required.',
            'selectedId.required' => $this->recommendationType === 'training_module' ? 'Training module is required.' : 'Competency is required.',
            'selectedId.integer' => $this->recommendationType === 'training_module' ? 'Training module is required.' : 'Competency is required.',
        ]);

        $year = (int) $this->selectedYear;

        if ((string) $this->recommendationType === 'training_module') {
            $trainingModule = TrainingModule::query()->with('competency')->find((int) $this->selectedId);
            if (!$trainingModule) {
                $this->error('Training module not found.', position: 'toast-top toast-center');
                return;
            }

            $competency = $trainingModule->competency;
            if (!$competency) {
                $this->error('Competency not found for selected training module.', position: 'toast-top toast-center');
                return;
            }

            if ((string) $competency->type !== (string) $this->group) {
                $this->error('Selected training module does not match the chosen group.', position: 'toast-top toast-center');
                return;
            }

            TrainingPlanRecom::updateOrCreate(
                [
                    'user_id' => $this->selectedUserId,
                    'year' => $year,
                    'training_module_id' => $trainingModule->id,
                ],
                [
                    'competency_id' => null,
                    'recommended_by' => Auth::id(),
                    'is_active' => true,
                ]
            );
        } else {
            $competency = Competency::find((int) $this->selectedId);
            if (!$competency) {
                $this->error('Competency not found.', position: 'toast-top toast-center');
                return;
            }

            if ((string) $competency->type !== (string) $this->group) {
                $this->error('Selected competency does not match the chosen group.', position: 'toast-top toast-center');
                return;
            }

            TrainingPlanRecom::updateOrCreate(
                [
                    'user_id' => $this->selectedUserId,
                    'year' => $year,
                    'competency_id' => $competency->id,
                ],
                [
                    'training_module_id' => null,
                    'recommended_by' => Auth::id(),
                    'is_active' => true,
                ]
            );
        }

        $this->selectedId = '';

        $this->loadRecommendations();
        $this->success('Recommendation saved.', position: 'toast-top toast-center');
    }

    public function toggleRecommendation(int $recommendationId): void
    {
        $this->authorizeAccess();

        $rec = TrainingPlanRecom::find($recommendationId);
        if (!$rec || (int) $rec->user_id !== (int) $this->selectedUserId || (int) $rec->year !== (int) $this->selectedYear) {
            return;
        }

        $rec->update(['is_active' => !$rec->is_active]);
        $this->loadRecommendations();
    }

    public function deleteRecommendation(int $recommendationId): void
    {
        $this->authorizeAccess();

        $rec = TrainingPlanRecom::find($recommendationId);
        if (!$rec || (int) $rec->user_id !== (int) $this->selectedUserId || (int) $rec->year !== (int) $this->selectedYear) {
            return;
        }

        $rec->delete();
        $this->loadRecommendations();
        $this->success('Recommendation removed.', position: 'toast-top toast-center');
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('pages.development.training-plan-recommendation', [
            'departments' => $this->departments,
            'sections' => $this->sections,
            'employeeOptions' => $this->employeeOptions,
        ]);
    }
}
