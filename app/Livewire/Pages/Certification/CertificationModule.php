<?php

namespace App\Livewire\Pages\Certification;

use App\Models\CertificationModule as CertificationModuleModel;
use App\Models\Competency;
use App\Exports\CertificationModuleExport;
use App\Exports\CertificationModuleTemplateExport;
use App\Imports\CertificationModuleImport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class CertificationModule extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $filter = '';
    public bool $modal = false;
    public string $mode = 'create'; // create|edit|preview
    public ?int $editingId = null;
    public $file;

    /**
     * Form state for the modal
     * code, competency, level, group_certification, points_per_module, new_gex, duration, major_component, mach_model
     */
    public array $form = [
        'code' => '',
        'module_title' => '',
        'competency_id' => null,
        'competency' => '',
        'level' => '',
        'group_certification' => '',
        'points_per_module' => null,
        'new_gex' => null,
        'duration' => null,
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

    /**
     * Options for competency selector.
     * Stored value in DB is a string label, e.g. "BC004 - Adaptability".
     */
    public array $competencyOptions = [];

    public function mount(): void
    {
        $this->loadCompetencyOptions();
    }

    private function formatCompetencyLabel(string $code, string $name): string
    {
        return trim($code . ' - ' . $name);
    }

    private function loadCompetencyOptions(?int $ensureId = null, string $search = ''): void
    {
        $query = Competency::query()
            ->select('id', 'code', 'name')
            ->orderBy('name');

        $search = trim($search);
        if ($search !== '') {
            $term = "%{$search}%";
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)->orWhere('name', 'like', $term);
            });
        }

        $options = $query
            ->limit(50)
            ->get()
            ->map(function ($c) {
                $label = $this->formatCompetencyLabel((string) $c->code, (string) $c->name);
                return ['id' => (int) $c->id, 'name' => $label];
            })
            ->values()
            ->all();

        if ($ensureId) {
            $existingIds = array_column($options, 'id');
            if (!in_array($ensureId, $existingIds, true)) {
                $selected = Competency::query()->select('id', 'code', 'name')->find($ensureId);
                if ($selected) {
                    $label = $this->formatCompetencyLabel((string) $selected->code, (string) $selected->name);
                    array_unshift($options, ['id' => (int) $selected->id, 'name' => $label]);
                }
            }
        }

        $this->competencyOptions = $options;
    }

    private function parseCompetencyIdFromLabel(?string $label): ?int
    {
        if (!is_string($label)) {
            return null;
        }
        $raw = trim($label);
        if ($raw === '') {
            return null;
        }
        $code = $raw;
        if (str_contains($raw, ' - ')) {
            $code = trim(explode(' - ', $raw, 2)[0]);
        }
        if ($code === '') {
            return null;
        }
        $id = Competency::query()->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function competencyLabelById(?int $id): ?string
    {
        if (!$id) {
            return null;
        }
        $c = Competency::query()->select('id', 'code', 'name')->find($id);
        if (!$c) {
            return null;
        }
        return $this->formatCompetencyLabel((string) $c->code, (string) $c->name);
    }

    public function searchCompetency(string $value = ''): void
    {
        $currentId = $this->form['competency_id'] ?? null;
        $this->loadCompetencyOptions(is_numeric($currentId) ? (int) $currentId : null, $value);
    }

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
            ['key' => 'code', 'label' => 'Code', 'class' => 'md:w-[10%]'],
            ['key' => 'module_title', 'label' => 'Module Title', 'class' => 'md:w-[28%]'],
            ['key' => 'group_certification', 'label' => 'Certif Group', 'class' => '!text-center md:w-[16%]'],
            ['key' => 'level', 'label' => 'Level', 'class' => '!text-center md:w-[12%]'],
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

    public function export()
    {
        return Excel::download(new CertificationModuleExport(), 'certification_modules.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new CertificationModuleTemplateExport(), 'certification_modules_template.xlsx');
    }

    public function updatedFile()
    {
        try {
            $this->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
            ]);

            Excel::import(new CertificationModuleImport(), $this->file);
            $this->dispatch('notify', type: 'success', message: 'Import completed');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Import failed: ' . $e->getMessage());
        } finally {
            $this->file = null;
        }
    }

    public function rules(): array
    {
        $uniqueCode = 'unique:certification_modules,code';
        if ($this->mode === 'edit' && $this->editingId) {
            $uniqueCode = 'unique:certification_modules,code,' . $this->editingId;
        }

        return [
            'form.code' => ['required', 'string', 'max:50', $uniqueCode],
            'form.module_title' => ['required', 'string', 'max:255'],
            'form.competency_id' => ['required', 'integer', 'exists:competency,id'],
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
            'module_title' => '',
            'competency_id' => null,
            'competency' => '',
            'level' => '',
            'group_certification' => '',
            'points_per_module' => null,
            'new_gex' => null,
            'duration' => null,
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
        $this->loadCompetencyOptions();
        $this->modal = true;
    }

    public function openEditModal(int $id): void
    {
        $model = CertificationModuleModel::findOrFail($id);
        $this->editingId = $model->id;

        $competencyId = $model->competency_id;
        if (!$competencyId) {
            $competencyId = $this->parseCompetencyIdFromLabel($model->competency);
        }

        $this->form = [
            'code' => $model->code,
            'module_title' => $model->module_title,
            'competency_id' => $competencyId ? (int) $competencyId : null,
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
        $this->loadCompetencyOptions($this->form['competency_id']);
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

            $competencyId = is_numeric($this->form['competency_id'] ?? null) ? (int) $this->form['competency_id'] : null;
            $competencyLabel = $this->competencyLabelById($competencyId);
            if (!$competencyId || $competencyLabel === null) {
                throw new \RuntimeException('Invalid competency selection.');
            }

            $attrs = [
                'code' => $this->form['code'],
                'module_title' => $this->form['module_title'],
                'level' => $this->form['level'],
                'competency_id' => $competencyId,
                'competency' => $competencyLabel,
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

    #[On('deleteModule')]
    public function deleteModule(int $id): void
    {
        try {
            $model = CertificationModuleModel::find($id);
            if ($model) {
                $model->delete();
                // Close the confirm dialog and notify
                $this->dispatch('confirm-done');
                $this->dispatch('notify', type: 'success', message: 'Module deleted');
            } else {
                $this->dispatch('confirm-done');
                $this->dispatch('notify', type: 'error', message: 'Module not found');
            }
        } catch (\Throwable $e) {
            $this->dispatch('confirm-done');
            $this->dispatch('notify', type: 'error', message: 'Failed to delete: ' . $e->getMessage());
        }
    }
}
