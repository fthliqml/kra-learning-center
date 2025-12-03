<?php

namespace App\Livewire\Pages\Development;

use App\Models\Competency;
use App\Models\CompetencyMatrix as CompetencyMatrixModel;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

/**
 * CompetencyMatrix Livewire Component
 *
 * Displays competency matrix with employees trained count.
 */
class CompetencyMatrix extends Component
{
    use Toast, WithPagination;

    public $search = '';
    public $filterType = '';

    // Detail modal
    public $detailModal = false;
    public $selectedCompetency = null;
    public $employeesTrained = [];

    public $typeOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function openDetailModal($competencyId)
    {
        $this->selectedCompetency = Competency::find($competencyId);

        if ($this->selectedCompetency) {
            $this->employeesTrained = CompetencyMatrixModel::with('employeeTrained')
                ->where('competency_id', $competencyId)
                ->get()
                ->map(fn($matrix) => $matrix->employeeTrained)
                ->filter()
                ->values()
                ->toArray();
        }

        $this->detailModal = true;
    }

    public function closeDetailModal()
    {
        $this->detailModal = false;
        $this->selectedCompetency = null;
        $this->employeesTrained = [];
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => 'text-center w-14'],
            ['key' => 'code', 'label' => 'ID', 'class' => 'text-center w-28'],
            ['key' => 'name', 'label' => 'Competency', 'class' => 'text-left min-w-[200px]'],
            ['key' => 'employees_trained_count', 'label' => 'Employees Trained', 'class' => 'text-center w-40'],
            ['key' => 'action', 'label' => 'Action', 'class' => 'text-center w-45', 'sortable' => false],
        ];
    }

    public function render()
    {
        $query = Competency::query()
            ->withCount('matrixEntries as employees_trained_count')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType, function ($q) {
                $q->where('type', $this->filterType);
            })
            ->orderBy('code');

        $competencies = $query->paginate(10);

        return view('pages.development.competency-matrix', [
            'competencies' => $competencies,
            'headers' => $this->headers(),
        ]);
    }
}
