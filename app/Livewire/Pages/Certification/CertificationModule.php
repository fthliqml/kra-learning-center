<?php

namespace App\Livewire\Pages\Certification;

use App\Models\CertificationModule as CertificationModuleModel;
use Livewire\Component;
use Livewire\WithPagination;

class CertificationModule extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = '';
    public bool $modal = false;
    public string $mode = 'create'; // create|edit|preview
    public ?int $editingId = null;

    /**
     * Form state for the modal
     * code, competency, level, group_certification, points_per_module, new_gex, duration, major_component, mach_model
     */
    public array $form = [
        'code' => '',
        'competency' => '',
        'level' => '',
        'group_certification' => '',
        'points_per_module' => 0,
        'new_gex' => 0.0,
        'duration' => 0,
        'major_component' => null,
        'mach_model' => null,
        'theory_passing_score' => null,
        'practical_passing_score' => null,
    ];

    public $groupOptions = [
        ['value' => 'Basic', 'label' => 'Basic'],
        ['value' => 'Intermediate', 'label' => 'Intermediate'],
        ['value' => 'Advanced', 'label' => 'Advanced'],
    ];

    public $groupCertificationOptions = [
        ['value' => 'ENGINE', 'label' => 'ENGINE'],
        ['value' => 'MACHINING', 'label' => 'MACHINING'],
        ['value' => 'PPT AND PPM', 'label' => 'PPT AND PPM'],
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 md:w-[8%]'],
            ['key' => 'code', 'label' => 'Code', 'class' => 'md:w-[5%]'],
            ['key' => 'level', 'label' => 'Level', 'class' => '!text-center md:w-[16%]'],
            ['key' => 'competency', 'label' => 'Competency', 'class' => 'md:w-[18%]'],
            ['key' => 'point', 'label' => 'Point', 'class' => '!text-center md:w-[10%]'],
            ['key' => 'new_gex', 'label' => 'New GEX', 'class' => '!text-center md:w-[12%]'],
            ['key' => 'duration', 'label' => 'Duration', 'class' => '!text-center md:w-[12%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center md:w-[14%]'],
        ];
    }

    public function modules()
    {
        $query = CertificationModuleModel::query()
            ->when($this->filter !== '', function ($q) {
                $q->where('level', $this->filter);
            })
            ->when(trim($this->search) !== '', function ($q) {
                $s = trim($this->search);
                $term = "%{$s}%";
                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhere('module_title', 'like', $term)
                        ->orWhere('competency', 'like', $term)
                        ->orWhere('level', 'like', $term);
                });
            })
            ->orderBy('code');

        $paginator = $query->paginate(10);

        return $paginator->through(function ($m, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $m->no = $start + $index;
            return $m;
        });
    }

    public function render()
    {
        return view('pages.certification.certification-module', [
            'headers' => $this->headers(),
            'modules' => $this->modules(),
        ]);
    }

    public function rules(): array
    {
        $uniqueCode = 'unique:certification_modules,code';
        if ($this->mode === 'edit' && $this->editingId) {
            $uniqueCode = 'unique:certification_modules,code,' . $this->editingId;
        }

        return [
            'form.code' => ['required', 'string', 'max:50', $uniqueCode],
            'form.competency' => ['required', 'string', 'max:255'],
            'form.level' => ['required', 'in:Basic,Intermediate,Advanced'],
            'form.group_certification' => ['required', 'in:ENGINE,MACHINING,PPT AND PPM'],
            'form.points_per_module' => ['required', 'integer', 'min:0'],
            'form.new_gex' => ['required', 'numeric', 'min:0'],
            'form.duration' => ['required', 'integer', 'min:1'],
            'form.major_component' => ['nullable', 'string'],
            'form.mach_model' => ['nullable', 'string'],
            'form.theory_passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.practical_passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function resetForm(): void
    {
        $this->form = [
            'code' => '',
            'competency' => '',
            'level' => '',
            'group_certification' => '',
            'points_per_module' => 0,
            'new_gex' => 0.0,
            'duration' => 0,
            'major_component' => null,
            'mach_model' => null,
            'theory_passing_score' => null,
            'practical_passing_score' => null,
        ];
        $this->editingId = null;
    }

    public function openCreateModal(): void
    {
        $this->mode = 'create';
        $this->resetForm();
        $this->modal = true;
    }

    public function openEditModal(int $id): void
    {
        $model = CertificationModuleModel::findOrFail($id);
        $this->editingId = $model->id;
        $this->form = [
            'code' => $model->code,
            'competency' => $model->competency,
            'level' => $model->level,
            'group_certification' => $model->group_certification,
            'points_per_module' => $model->points_per_module,
            'new_gex' => (float) $model->new_gex,
            'duration' => (int) $model->duration,
            'major_component' => $model->major_component,
            'mach_model' => $model->mach_model,
            'theory_passing_score' => (float) $model->theory_passing_score,
            'practical_passing_score' => (float) $model->practical_passing_score,
        ];
        $this->mode = 'edit';
        $this->modal = true;
    }

    public function openDetailModal(int $id): void
    {
        $this->openEditModal($id);
        $this->mode = 'preview';
    }

    public function save(): void
    {
        try {
            $this->validate();

            $attrs = [
                'code' => $this->form['code'],
                'module_title' => $this->form['competency'],
                'level' => $this->form['level'],
                'competency' => $this->form['competency'],
                'group_certification' => $this->form['group_certification'],
                'points_per_module' => (int) $this->form['points_per_module'],
                'new_gex' => (float) $this->form['new_gex'],
                'duration' => (int) $this->form['duration'],
                'major_component' => $this->form['major_component'],
                'mach_model' => $this->form['mach_model'],
                'theory_passing_score' => (float) $this->form['theory_passing_score'],
                'practical_passing_score' => (float) $this->form['practical_passing_score'],
                'is_active' => true,
            ];

            if ($this->mode === 'edit' && $this->editingId) {
                $model = CertificationModuleModel::findOrFail($this->editingId);
                $model->update($attrs);
            } else {
                CertificationModuleModel::create($attrs);
            }

            $this->modal = false;
            $this->dispatch('notify', type: 'success', message: 'Certification module saved');
            $this->resetForm();
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to save: ' . $e->getMessage());
        }
    }

    public function deleteModule(int $id): void
    {
        try {
            $model = CertificationModuleModel::find($id);
            if ($model) {
                $model->delete();
                $this->dispatch('notify', type: 'success', message: 'Module deleted');
            }
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to delete: ' . $e->getMessage());
        }
    }
}
