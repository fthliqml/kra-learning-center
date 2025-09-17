<?php

namespace App\Livewire\Pages\Training;

use App\Exports\TrainingModuleExport;
use App\Exports\TrainingModuleTemplateExport;
use App\Imports\TrainingModuleImport;
use App\Models\TrainingModule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class Module extends Component
{
    use Toast, WithPagination, WithFileUploads;
    public $file;
    public $modal = false;
    public $selectedId = null;
    public $mode = 'create'; //  create | edit | preview
    public $search = '';
    public $filter = null;

    public $groupOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $formData = [
        'title' => '',
        'group_comp' => '',
        'objective' => '',
        'training_content' => '',
        'method' => '',
        'duration' => '',
        'frequency' => '',
    ];

    protected $rules = [
        'formData.title' => 'required|string|max:255',
        'formData.group_comp' => 'required|string',
        'formData.objective' => 'nullable|string',
        'formData.training_content' => 'nullable|string',
        'formData.method' => 'nullable|string|max:255',
        'formData.duration' => 'required|numeric|min:1',
        'formData.frequency' => 'required|integer|min:1',
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['formData', 'selectedId']);
        $this->mode = 'create';
        $this->modal = true;

        $this->resetValidation();
    }
    public function openEditModal($id)
    {
        $module = TrainingModule::findOrFail($id);

        $this->selectedId = $id;
        $this->formData = [
            'title' => $module->title,
            'group_comp' => $module->group_comp,
            'objective' => $module->objective,
            'training_content' => $module->training_content,
            'method' => $module->method,
            'duration' => $module->duration,
            'frequency' => $module->frequency,
        ];

        $this->mode = 'edit';
        $this->modal = true;

        $this->resetValidation();
    }

    public function save()
    {

        $this->validate([
            'formData.title' => 'required|string|max:255',
            'formData.group_comp' => 'required|string',
            'formData.objective' => 'required|string',
            'formData.training_content' => 'required|string',
            'formData.method' => 'required|string',
            'formData.duration' => 'required|numeric|min:1',
            'formData.frequency' => 'required|numeric|min:1',
        ]);

        if ($this->mode === 'create') {
            TrainingModule::create($this->formData);
            $this->success('Berhasil menambahkan data baru', position: 'toast-top toast-center');
        } else {
            TrainingModule::where('id', $this->selectedId)->update($this->formData);
            $this->success('Berhasil memperbarui data', position: 'toast-top toast-center');
        }

        $this->modal = false;

    }

    #[On('deleteModule')]
    public function deleteModule($id)
    {
        TrainingModule::findOrFail($id)->delete();

        $this->error('Berhasil menghapus data', position: 'toast-top toast-center');

    }

    public function headers()
    {
        return [
            ['key' => 'id', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'title', 'label' => 'Module Title', 'class' => 'w-[300px]'],
            ['key' => 'group_comp', 'label' => 'Group Comp', 'class' => '!text-center'],
            [
                'key' => 'duration',
                'label' => 'Duration',
                'class' => '!text-center',
                'format' => fn($row, $field) => $field . ' Hours',
            ],
            [
                'key' => 'frequency',
                'label' => 'Frequency',
                'class' => '!text-center',
                'format' => fn($row, $field) => $field . ' Days',
            ],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function modules()
    {
        return TrainingModule::query()
            ->when(
                $this->search,
                fn($q) =>
                $q->where('title', 'like', '%' . $this->search . '%')
            )
            ->when(
                $this->filter,
                fn($q) =>
                $q->where('group_comp', $this->filter)
            )
            ->orderBy('created_at', 'asc')
            ->paginate(10)
            ->onEachSide(1);
    }

    public function export()
    {
        return Excel::download(new TrainingModuleExport(), 'training_modules.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new TrainingModuleTemplateExport(), 'training_module_template.xlsx');
    }

    public function updatedFile()
    {
        if (!$this->file)
            return;

        $this->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        Excel::import(new TrainingModuleImport, $this->file);

        $this->success('Berhasil menambah data', position: 'toast-top toast-center');
        $this->file = null;
    }

    public function render()
    {

        return view('pages.training.training-module', [
            'modules' => $this->modules(),
            'headers' => $this->headers()
        ]);

    }
}
