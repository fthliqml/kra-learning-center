<?php

namespace App\Livewire\Pages\Training;

use App\Models\TrainingModule;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class Module extends Component
{
    use Toast;
    public $modal = false;
    public $selectedId = null;
    public $mode = 'create'; //  create | edit | preview

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

    public function openPreviewModal($id)
    {
        $module = TrainingModule::findOrFail($id);

        $this->resetValidation();
        $this->formData = $module->toArray();
        $this->mode = 'preview';
        $this->modal = true;
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

    public function render()
    {
        $modules = TrainingModule::all();

        return view('livewire.pages.training.module.index', [
            'modules' => $modules,
            'headers' => $this->headers()
        ]);

    }
}
