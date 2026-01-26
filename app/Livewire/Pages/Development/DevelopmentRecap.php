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
    public array $selectedTrainingPlanIds = [];

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
            $this->selectedTrainingPlanIds = [];
        }
    }

    public function createTrainingSchedule()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403);
        }

        $planIds = collect($this->selectedTrainingPlanIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($planIds->isEmpty()) {
            $this->addError('selectedTrainingPlanIds', 'Please select at least one plan.');
            return;
        }

        $plans = TrainingPlan::query()
            ->select('id', 'user_id', 'competency_id', 'training_module_id')
            ->whereIn('id', $planIds->all())
            ->get();

        if ($plans->isEmpty()) {
            $this->addError('selectedTrainingPlanIds', 'Selected plans not found.');
            return;
        }

        $participants = $plans
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        if ($participants->isEmpty()) {
            $this->addError('selectedTrainingPlanIds', 'No valid participants found.');
            return;
        }

        $moduleIds = $plans
            ->pluck('training_module_id')
            ->filter()
            ->unique()
            ->values();

        $competencyIds = $plans
            ->pluck('competency_id')
            ->filter()
            ->unique()
            ->values();

        // Admin should pick the same plan across employees.
        // Enforce: either 1 module_id (module-based plans), or 1 competency_id (competency-based plans).
        if ($moduleIds->count() > 1) {
            $this->addError('selectedTrainingPlanIds', 'Please select plans with the same Training Module.');
            return;
        }

        if ($moduleIds->count() === 1) {
            // Mixed module + competency-only selections are not allowed
            if ($plans->whereNull('training_module_id')->isNotEmpty()) {
                $this->addError('selectedTrainingPlanIds', 'Please select either module-based plans or competency-based plans (not mixed).');
                return;
            }

            return redirect()->route('training-schedule.index', [
                'participants' => $participants->implode(','),
                'prefill_module_id' => (int) $moduleIds->first(),
            ]);
        }

        if ($competencyIds->count() !== 1) {
            $this->addError('selectedTrainingPlanIds', 'Please select plans with the same Competency.');
            return;
        }

        return redirect()->route('training-schedule.index', [
            'participants' => $participants->implode(','),
            'prefill_competency_id' => (int) $competencyIds->first(),
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
        return [
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center w-24'],
            ['key' => 'name', 'label' => 'Employee Name', 'class' => 'min-w-[200px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => 'min-w-[180px]'],
            ['key' => 'plan1', 'label' => 'Plan 1', 'class' => 'min-w-[200px]'],
            ['key' => 'plan2', 'label' => 'Plan 2', 'class' => 'min-w-[200px]'],
            ['key' => 'plan3', 'label' => 'Plan 3', 'class' => 'min-w-[200px]'],
        ];
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
                    ->with(['competency', 'trainingModule'])
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

            $planYear = (int) $this->selectedYear;

            $planCells = $plans->map(function (TrainingPlan $plan) use ($planYear) {
                $label = $plan->trainingModule?->title
                    ?: $plan->competency?->name
                    ?: '-';

                // "Scheduled" means there is a training schedule in this year for the chosen module,
                // or (if module not chosen) for the competency.
                $scheduled = false;

                if (!empty($plan->training_module_id)) {
                    $scheduled = Training::query()
                        ->whereYear('start_date', $planYear)
                        ->where('module_id', $plan->training_module_id)
                        ->exists();
                } elseif (!empty($plan->competency_id)) {
                    $scheduled = Training::query()
                        ->whereYear('start_date', $planYear)
                        ->where(function ($q) use ($plan) {
                            $q->where('competency_id', $plan->competency_id)
                                ->orWhereHas('module', function ($mq) use ($plan) {
                                    $mq->where('competency_id', $plan->competency_id);
                                })
                                ->orWhereHas('course', function ($cq) use ($plan) {
                                    $cq->where('competency_id', $plan->competency_id);
                                });
                        })
                        ->exists();
                }

                return [
                    'id' => (int) $plan->id,
                    'label' => $label,
                    'scheduled' => $scheduled,
                ];
            })->values();

            $plan1 = $planCells[0] ?? ['id' => null, 'label' => '-', 'scheduled' => false];
            $plan2 = $planCells[1] ?? ['id' => null, 'label' => '-', 'scheduled' => false];
            $plan3 = $planCells[2] ?? ['id' => null, 'label' => '-', 'scheduled' => false];

            return (object) [
                'user_id' => $user->id,
                'no' => $start + $index,
                'nrp' => $user->nrp,
                'name' => $user->name,
                'section' => $user->section,
                'plan1' => $plan1,
                'plan2' => $plan2,
                'plan3' => $plan3,
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
