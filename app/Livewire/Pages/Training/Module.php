<?php

namespace App\Livewire\Pages\Training;

use App\Exports\TrainingModuleExport;
use App\Exports\TrainingModuleTemplateExport;
use App\Imports\TrainingModuleImport;
use App\Models\Competency;
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

    public $formData = [
        'title' => '',
        'competency_id' => '',
        'objective' => '',
        'training_content' => '',
        'method' => '',
        'duration' => '',
        'frequency' => '',
    ];

    protected $rules = [
        'formData.title' => 'required|string|max:255',
        'formData.competency_id' => 'required|exists:competency,id',
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
    public function openDetailModal($id)
    {
        $module = TrainingModule::with('competency')->findOrFail($id);

        $this->selectedId = $id;
        $this->formData = [
            'title' => $module->title,
            'competency_id' => $module->competency->name ?? '-',
            'objective' => $module->objective,
            'training_content' => $module->training_content,
            'method' => $module->method,
            'duration' => $module->duration,
            'frequency' => $module->frequency,
        ];

        $this->mode = 'preview';
        $this->modal = true;

        $this->resetValidation();
    }

    public function openEditModal($id)
    {
        $module = TrainingModule::findOrFail($id);

        $this->selectedId = $id;
        $this->formData = [
            'title' => $module->title,
            'competency_id' => $module->competency_id,
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
            'formData.competency_id' => 'required|exists:competency,id',
            'formData.objective' => 'required|string',
            'formData.training_content' => 'required|string',
            'formData.method' => 'required|string',
            'formData.duration' => 'required|numeric|min:1',
            'formData.frequency' => 'required|numeric|min:1',
        ]);

        if ($this->mode === 'create') {
            TrainingModule::create($this->formData);
            $this->success('Successfully added new module', position: 'toast-top toast-center');
        } else {
            TrainingModule::where('id', $this->selectedId)->update($this->formData);
            $this->success('Successfully updated module', position: 'toast-top toast-center');
        }

        $this->modal = false;
    }

    #[On('deleteModule')]
    public function deleteModule($id)
    {
        TrainingModule::findOrFail($id)->delete();

        $this->error('Module deleted', position: 'toast-top toast-center');
        // Close confirm dialog and stop spinner
        $this->dispatch('confirm-done');
    }

    public function headers()
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'title', 'label' => 'Module Title', 'class' => 'w-[400px]'],
            ['key' => 'competency.name', 'label' => 'Competency', 'class' => 'min-w-[150px]'],
            ['key' => 'competency.type', 'label' => 'Group Comp', 'class' => '!text-center'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function modules()
    {
        $query = TrainingModule::with('competency')
            ->when(
                $this->search,
                fn($q) =>
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhereHas('competency', fn($q2) => $q2->where('name', 'like', '%' . $this->search . '%'))
            )
            ->when(
                $this->filter,
                fn($q) =>
                $q->whereHas('competency', fn($q2) => $q2->where('type', $this->filter))
            )
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function ($modules, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $modules->no = $start + $index;
            return $modules;
        });
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

        try {
            Excel::import(new TrainingModuleImport, $this->file);

            $this->success('Successfully imported modules', position: 'toast-top toast-center');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = collect($e->failures());
            $errors = $failures->map(function ($f) {
                return "Row {$f->row()}: " . implode(', ', $f->errors());
            });

            if ($errors->count() === 1) {
                $this->error($errors->first(), position: 'toast-top toast-center');
            } else {
                $list = $errors->map(fn($err) => "<li>{$err}</li>")->implode('');
                $this->error("<ul class='list-disc pl-5'>{$list}</ul>", position: 'toast-top toast-center', timeout: 5000);
            }
        } finally {
            $this->file = null;
        }
    }



    public function render()
    {
        $competencyOptions = Competency::orderBy('name')
            ->get()
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name . ' (' . $c->type . ')'])
            ->toArray();

        // Group comp filter options - only unique types
        $groupCompOptions = Competency::select('type')
            ->distinct()
            ->orderBy('type')
            ->get()
            ->map(fn($c) => ['value' => $c->type, 'label' => $c->type])
            ->toArray();

        return view('pages.training.training-module', [
            'modules' => $this->modules(),
            'headers' => $this->headers(),
            'competencyOptions' => $competencyOptions,
            'groupCompOptions' => $groupCompOptions,
        ]);
    }
}
