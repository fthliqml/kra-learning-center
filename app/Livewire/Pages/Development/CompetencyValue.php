<?php

namespace App\Livewire\Pages\Development;

use App\Exports\CompetencyValueExport;
use App\Exports\CompetencyValueTemplateExport;
use App\Imports\CompetencyValueImport;
use App\Models\Competency;
use App\Models\CompetencyValue as CompetencyValueModel;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

/**
 * CompetencyValue Livewire Component
 *
 * Manages competency value data with CRUD operations.
 */
class CompetencyValue extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public $modal = false;
    public $selectedId = null;
    public $mode = 'create';
    public $search = '';
    public $filterType = '';
    public $file;

    public $typeOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $positionOptions = [
        ['value' => 'Division Head', 'label' => 'Division Head'],
        ['value' => 'Department Head', 'label' => 'Department Head'],
        ['value' => 'Section Head', 'label' => 'Section Head'],
        ['value' => 'Foreman', 'label' => 'Foreman'],
        ['value' => 'Staff', 'label' => 'Staff'],
    ];

    public array $competencyOptions = [];

    public $formType = '';

    public $formData = [
        'competency_id' => '',
        'position' => '',
        'bobot' => '',
        'value' => '',
    ];

    public function mount()
    {
        $this->loadCompetencyOptions();
    }

    public function loadCompetencyOptions(): void
    {
        if (empty($this->formType)) {
            $this->competencyOptions = [];
            return;
        }

        $query = Competency::orderBy('code')
            ->where('type', $this->formType);

        $this->competencyOptions = $query->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'type' => $c->type,
            ])
            ->toArray();
    }

    public function updatedFormType(): void
    {
        $this->loadCompetencyOptions();
        // Reset competency selection when type changes
        $this->formData['competency_id'] = '';
    }

    protected function rules()
    {
        return [
            'formData.competency_id' => 'required|exists:competency,id',
            'formData.position' => 'required|string|max:255',
            'formData.bobot' => 'required|string|max:50',
            'formData.value' => 'required|integer|min:1|max:10',
        ];
    }

    protected function messages(): array
    {
        return [
            'formData.competency_id.required' => 'Please select a competency.',
            'formData.competency_id.exists' => 'Selected competency is invalid.',
            'formData.position.required' => 'Position is required.',
            'formData.bobot.required' => 'Bobot is required.',
            'formData.value.required' => 'Value is required.',
            'formData.value.integer' => 'Value must be a number.',
            'formData.value.min' => 'Value must be at least 1.',
            'formData.value.max' => 'Value may not exceed 10.',
        ];
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['formData', 'selectedId', 'formType']);
        $this->formData = [
            'competency_id' => '',
            'position' => '',
            'bobot' => '',
            'value' => '',
        ];
        $this->formType = '';
        $this->loadCompetencyOptions();
        $this->mode = 'create';
        $this->modal = true;
        $this->resetValidation();
    }

    public function openEditModal($id)
    {
        $competencyValue = CompetencyValueModel::with('competency')->findOrFail($id);
        $this->selectedId = $id;
        $this->formType = $competencyValue->competency?->type ?? '';
        $this->loadCompetencyOptions();
        $this->formData = [
            'competency_id' => $competencyValue->competency_id,
            'position' => $competencyValue->position,
            'bobot' => $competencyValue->bobot,
            'value' => $competencyValue->value,
        ];
        $this->mode = 'edit';
        $this->modal = true;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'competency_id' => $this->formData['competency_id'],
            'position' => trim($this->formData['position']),
            'bobot' => trim($this->formData['bobot']),
            'value' => (int) $this->formData['value'],
        ];

        if ($this->mode === 'create') {
            CompetencyValueModel::create($data);
            $this->success('Competency value created successfully.', position: 'toast-top toast-center');
        } else {
            $competencyValue = CompetencyValueModel::findOrFail($this->selectedId);
            $competencyValue->update($data);
            $this->success('Competency value updated successfully.', position: 'toast-top toast-center');
        }

        $this->modal = false;
        $this->reset(['formData', 'selectedId']);
    }

    #[On('deleteCompetencyValue')]
    public function delete($id = null)
    {
        if (!$id) {
            return;
        }

        $competencyValue = CompetencyValueModel::find($id);
        if ($competencyValue) {
            $competencyValue->delete();
            $this->success('Competency value deleted successfully.', position: 'toast-top toast-center');
        }

        $this->dispatch('confirm-done');
    }

    /**
     * Export competency values to Excel.
     */
    public function export()
    {
        return Excel::download(new CompetencyValueExport, 'competency-values.xlsx');
    }

    /**
     * Download template Excel for import.
     */
    public function downloadTemplate()
    {
        return Excel::download(new CompetencyValueTemplateExport, 'competency-value-template.xlsx');
    }

    /**
     * Handle file upload and import.
     */
    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $import = new CompetencyValueImport();
            Excel::import($import, $this->file->getRealPath());

            $message = "Import completed. Created: {$import->created}, Updated: {$import->updated}, Skipped: {$import->skipped}";
            $this->success($message, position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage(), position: 'toast-top toast-center');
        }

        $this->reset('file');
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => 'text-center w-14'],
            ['key' => 'code', 'label' => 'ID', 'class' => 'text-center w-28'],
            ['key' => 'competency_name', 'label' => 'Competency', 'class' => 'text-left min-w-[200px]'],
            ['key' => 'type', 'label' => 'Type', 'class' => 'text-center w-24'],
            ['key' => 'position', 'label' => 'Position', 'class' => 'text-center w-40'],
            ['key' => 'bobot', 'label' => 'Bobot', 'class' => 'text-center w-20'],
            ['key' => 'value', 'label' => 'Value', 'class' => 'text-center w-20'],
            ['key' => 'action', 'label' => 'Action', 'class' => 'text-center w-45', 'sortable' => false],
        ];
    }

    public function render()
    {
        $query = CompetencyValueModel::query()
            ->with('competency')
            ->when($this->search, function ($q) {
                $q->whereHas('competency', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType, function ($q) {
                $q->whereHas('competency', function ($sub) {
                    $sub->where('type', $this->filterType);
                });
            })
            ->orderBy('id', 'desc');

        $competencyValues = $query->paginate(10);

        return view('pages.development.competency-value', [
            'competencyValues' => $competencyValues,
            'headers' => $this->headers(),
        ]);
    }
}
