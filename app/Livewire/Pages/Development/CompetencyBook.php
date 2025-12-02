<?php

namespace App\Livewire\Pages\Development;

use App\Exports\CompetencyBookExport;
use App\Exports\CompetencyBookTemplateExport;
use App\Imports\CompetencyBookImport;
use App\Models\Competency;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

/**
 * CompetencyBook Livewire Component
 *
 * Manages competency data with CRUD operations.
 */
class CompetencyBook extends Component
{
    use Toast, WithPagination, WithFileUploads;

    public $modal = false;
    public $selectedId = null;
    public $mode = 'create';
    public $search = '';
    public $filter = '';
    public $file;

    public $typeOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $formData = [
        'name' => '',
        'type' => '',
        'description' => '',
    ];

    protected function rules()
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.type' => 'required|in:BMC,BC,MMP,LC,MDP,TOC',
            'formData.description' => 'required|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'formData.name.required' => 'Competency name is required.',
            'formData.name.max' => 'Competency name may not exceed 255 characters.',
            'formData.type.required' => 'Please select a competency type.',
            'formData.type.in' => 'Invalid competency type.',
            'formData.description.required' => 'Description is required.',
            'formData.description.max' => 'Description may not exceed 1000 characters.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'formData.name' => 'competency name',
            'formData.type' => 'type',
            'formData.description' => 'description',
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
        $this->reset(['formData', 'selectedId']);
        $this->formData = [
            'name' => '',
            'type' => '',
            'description' => '',
        ];
        $this->mode = 'create';
        $this->modal = true;
        $this->resetValidation();
    }

    public function openDetailModal($id)
    {
        $competency = Competency::findOrFail($id);
        $this->selectedId = $id;
        $this->formData = [
            'name' => $competency->name,
            'type' => $competency->type,
            'description' => $competency->description,
        ];
        $this->mode = 'preview';
        $this->modal = true;
        $this->resetValidation();
    }

    public function openEditModal($id)
    {
        $competency = Competency::findOrFail($id);
        $this->selectedId = $id;
        $this->formData = [
            'name' => $competency->name,
            'type' => $competency->type,
            'description' => $competency->description,
        ];
        $this->mode = 'edit';
        $this->modal = true;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => trim($this->formData['name']),
            'type' => $this->formData['type'],
            'description' => trim($this->formData['description']),
        ];

        if ($this->mode === 'create') {
            $data['code'] = Competency::generateCode($this->formData['type']);
            Competency::create($data);
            $this->success('Competency created successfully.', position: 'toast-top toast-center');
        } else {
            $competency = Competency::findOrFail($this->selectedId);
            $competency->update($data);
            $this->success('Competency updated successfully.', position: 'toast-top toast-center');
        }

        $this->modal = false;
        $this->reset(['formData', 'selectedId']);
    }

    #[On('deleteCompetency')]
    public function delete($id = null)
    {
        if (!$id) {
            return;
        }

        $competency = Competency::find($id);
        if ($competency) {
            $competency->delete();
            $this->success('Competency deleted successfully.', position: 'toast-top toast-center');
        }

        $this->dispatch('confirm-done');
    }

    public function export()
    {
        try {
            $this->success('Processing competency data export...', position: 'toast-top toast-center');
            return Excel::download(new CompetencyBookExport(), 'competency_book_' . date('Y-m-d_H-i-s') . '.xlsx');
        } catch (\Exception $e) {
            $this->error('Failed to export competency data. Please try again.', position: 'toast-top toast-center');
        }
    }

    public function downloadTemplate()
    {
        try {
            $this->success('Downloading Excel template...', position: 'toast-top toast-center');
            return Excel::download(new CompetencyBookTemplateExport(), 'template_competency_book_' . date('Y-m-d') . '.xlsx');
        } catch (\Exception $e) {
            $this->error('Failed to download template. Please try again.', position: 'toast-top toast-center');
        }
    }

    public function updatedFile()
    {
        if (!$this->file)
            return;

        try {
            $this->validate([
                'file' => 'required|mimes:xlsx,xls',
            ]);

            $this->success('Processing competency data import...', position: 'toast-top toast-center');

            $import = new CompetencyBookImport();
            Excel::import($import, $this->file);

            // Build summary message
            $parts = [];
            $totalProcessed = $import->created + $import->updated + $import->skipped;

            if (!empty($import->created)) {
                $parts[] = "{$import->created} new competency(s) added";
            }
            if (!empty($import->updated)) {
                $parts[] = "{$import->updated} competency(s) updated";
            }
            if (!empty($import->skipped)) {
                $parts[] = "{$import->skipped} row(s) failed";
            }

            if ($totalProcessed === 0) {
                $this->warning('No data processed. Ensure the Excel file has the correct format.', position: 'toast-top toast-center');
            } elseif ($import->skipped > 0 && ($import->created + $import->updated) === 0) {
                $this->error('Import failed! All rows were invalid. Check data format in the Excel file.', position: 'toast-top toast-center');
            } elseif ($import->skipped > 0) {
                $summary = 'Import finished with warnings: ' . implode(', ', $parts);
                $this->warning($summary, position: 'toast-top toast-center');
            } else {
                $summary = 'Import successful! ' . implode(', ', $parts);
                $this->success($summary, position: 'toast-top toast-center');
            }

            $this->file = null;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error('Invalid file uploaded. Use Excel file (.xlsx or .xls)', position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Error occurred while importing: ' . $e->getMessage(), position: 'toast-top toast-center');
            $this->file = null;
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => 'text-center w-14'],
            ['key' => 'code', 'label' => 'ID', 'class' => 'text-center w-28'],
            ['key' => 'name', 'label' => 'Competency', 'class' => 'text-left min-w-[200px]'],
            ['key' => 'type', 'label' => 'Type', 'class' => 'text-center w-28'],
            ['key' => 'action', 'label' => 'Action', 'class' => 'text-center w-55', 'sortable' => false],
        ];
    }

    public function render()
    {
        $query = Competency::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filter, function ($q) {
                $q->where('type', $this->filter);
            })
            ->orderBy('id', 'desc');

        $competencies = $query->paginate(10);

        return view('pages.development.competency-book', [
            'competencies' => $competencies,
            'headers' => $this->headers(),
        ]);
    }
}
