<?php

namespace App\Livewire\Pages\Development;

use App\Exports\DevelopmentRecapExport;
use App\Models\Training;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DevelopmentRecap extends Component
{
    use WithPagination;

    public string $selectedYear;
    public string $search = '';

    /** @var array<int> */
    public array $selectedEmployeeIds = [];

    public function mount(): void
    {
        $this->selectedYear = (string) now()->year;
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property !== '') {
            $this->resetPage();
        }

        // Defensive: changing filters/search should reset selections.
        if (in_array((string) $property, ['selectedYear', 'search'], true)) {
            $this->selectedEmployeeIds = [];
        }
    }

    public function createTrainingSchedule()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403);
        }

        $ids = collect($this->selectedEmployeeIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->addError('selectedEmployeeIds', 'Please select at least one employee.');
            return;
        }

        return redirect()->route('training-schedule.index', [
            'participants' => $ids->implode(','),
        ]);
    }

    public function export()
    {
        $year = (int) $this->selectedYear;
        $fileName = 'development_recap_' . $year . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new DevelopmentRecapExport($year, $this->search), $fileName);
    }

    public function headers(): array
    {
        $headers = [];

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->hasRole('admin')) {
            $headers[] = ['key' => 'select', 'label' => '', 'class' => '!text-center w-12'];
        }

        return array_merge($headers, [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-24'],
            ['key' => 'name', 'label' => 'Employee Name', 'class' => 'min-w-[200px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => 'min-w-[180px]'],
            ['key' => 'plan1', 'label' => 'Plan 1', 'class' => 'min-w-[200px]'],
            ['key' => 'plan2', 'label' => 'Plan 2', 'class' => 'min-w-[200px]'],
            ['key' => 'plan3', 'label' => 'Plan 3', 'class' => 'min-w-[200px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center w-32'],
        ]);
    }

    protected function getRows()
    {
        $year = (int) $this->selectedYear;

        $query = User::query()
            ->whereHas('trainingPlans', function ($q) use ($year) {
                $q->where('year', $year)
                    ->where('status', TrainingPlan::STATUS_APPROVED);
            })
            ->with(['trainingPlans' => function ($q) use ($year) {
                $q->where('year', $year)
                    ->where('status', TrainingPlan::STATUS_APPROVED)
                    ->with('competency')
                    ->orderBy('id');
            }]);

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('nrp', 'like', $term)
                    ->orWhere('section', 'like', $term);
            });
        }

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function (User $user, int $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $plans = $user->trainingPlans->take(3)->values();

            $planLabels = $plans->map(function (TrainingPlan $plan) {
                return $plan->competency?->name ?? '-';
            });

            // Determine status: 'scheduled' if there is any training for any of the competencies in this year,
            // otherwise 'waiting'.
            $competencyIds = $plans
                ->pluck('competency_id')
                ->filter()
                ->unique()
                ->values();

            $hasScheduledTraining = false;

            if ($competencyIds->isNotEmpty()) {
                $hasScheduledTraining = Training::query()
                    ->whereYear('start_date', $planYear = (int) $this->selectedYear)
                    ->where(function ($q) use ($competencyIds) {
                        $q->whereIn('competency_id', $competencyIds)
                            ->orWhereHas('module', function ($mq) use ($competencyIds) {
                                $mq->whereIn('competency_id', $competencyIds);
                            })
                            ->orWhereHas('course', function ($cq) use ($competencyIds) {
                                $cq->whereIn('competency_id', $competencyIds);
                            });
                    })
                    ->exists();
            }

            $statusLabel = $hasScheduledTraining ? 'scheduled' : 'waiting';

            return (object) [
                'user_id' => $user->id,
                'no' => $start + $index,
                'nrp' => $user->nrp,
                'name' => $user->name,
                'section' => $user->section,
                'plan1' => $planLabels[0] ?? '-',
                'plan2' => $planLabels[1] ?? '-',
                'plan3' => $planLabels[2] ?? '-',
                'status' => $statusLabel,
            ];
        });
    }

    public function render()
    {
        return view('pages.development.development-recap', [
            'headers' => $this->headers(),
            'rows' => $this->getRows(),
        ]);
    }
}
