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
    public $employeesTrainedText = '';

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
        // Load competency basic info
        $this->selectedCompetency = Competency::find($competencyId);

        // Always derive employee list directly from competency_matrixs table
        // so it matches the withCount('matrixEntries') used in the main table.
        $matrixRows = CompetencyMatrixModel::with('employeeTrained')
            ->where('competency_id', $competencyId)
            ->get();

        $this->employeesTrained = $matrixRows
            ->map(function ($row) {
                $employee = $row->employeeTrained;
                return [
                    'name' => $employee->name ?? 'Unknown Employee (ID ' . $row->employees_trained_id . ')',
                ];
            })
            ->toArray();

        // Pre-format text lines "1. Name" for the textarea
        $this->employeesTrainedText = collect($this->employeesTrained)
            ->values()
            ->map(fn($employee, $index) => ($index + 1) . '. ' . ($employee['name'] ?? '-'))
            ->implode("\n");

        if (!$this->selectedCompetency) {
            $this->employeesTrainedText = '';
        }

        $this->detailModal = true;
    }

    public function closeDetailModal()
    {
        $this->detailModal = false;
        $this->selectedCompetency = null;
        $this->employeesTrained = [];
        $this->employeesTrainedText = '';
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
