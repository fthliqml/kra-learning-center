<?php

namespace App\Livewire\Pages\Training;

use App\Exports\DataTrainerExport;
use App\Exports\DataTrainerTemplateExport;
use App\Imports\DataTrainerImport;
use App\Models\Competency;
use App\Models\Trainer;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

class DataTrainer extends Component
{
    use Toast, WithPagination, WithFileUploads;
    public $modal = false;
    public $selectedId = null;
    public $mode = 'create'; //  create | edit | preview
    public $search = '';
    public $users = [];
    public $file;
    public $filter = '';

    public function mount()
    {
        $this->users = User::all()->map(function ($user) {
            return [
                'value' => $user->id,
                'label' => $user->name,
            ];
        })->toArray();
    }



    public $groupOptions = [
        ['value' => 'Internal', 'label' => 'Internal'],
        ['value' => 'External', 'label' => 'External'],
    ];

    public $formData = [
        'trainer_type' => '',
        'name' => '',
        'user_id' => '',
        'institution' => '',
        'competencies' => [''],
    ];

    protected function rules()
    {
        $isInternal = ($this->formData['trainer_type'] ?? 'internal') === 'internal';
        return [
            'formData.trainer_type' => 'required|in:internal,external',
            'formData.user_id' => [$isInternal ? 'required' : 'nullable'],
            'formData.name' => [$isInternal ? 'nullable' : 'required', 'string', 'max:255'],
            'formData.institution' => 'required|string',
            'formData.competencies' => 'required|array|min:1',
            'formData.competencies.*' => 'nullable|string|max:255',
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
            'trainer_type' => '',
            'name' => '',
            'user_id' => '',
            'institution' => '',
            'competencies' => [''],
        ];
        $this->mode = 'create';
        $this->modal = true;

        $this->resetValidation();
    }

    // Auto-fill institution when switching to internal trainer type
    public function updatedFormDataTrainerType($value): void
    {
        if ($value === 'internal') {
            $this->formData['institution'] = 'PT Komatsu Remanufacturing Asia';
            // Clear external-only field
            $this->formData['name'] = '';
        } else {
            $this->formData['institution'] = '';
            // Clear internal-only field
            $this->formData['user_id'] = '';
        }
    }

    public function openDetailModal($id)
    {
        $trainer = Trainer::findOrFail($id);

        // Ambil semua competency dari relasi pivot
        $competencyDescs = $trainer->competencies->pluck('description')->toArray();

        $this->selectedId = $id;
        $isInternal = !is_null($trainer->user_id);
        $this->formData = [
            'trainer_type' => $isInternal ? 'internal' : 'external',
            'name' => $isInternal ? ($trainer->user->name ?? '') : ($trainer->name ?? ''),
            'user_id' => $trainer->user_id,
            'institution' => $trainer->institution,
            'competencies' => !empty($competencyDescs) ? $competencyDescs : [''],
        ];

        $this->mode = 'preview';
        $this->modal = true;

        $this->resetValidation();
    }

    public function openEditModal($id)
    {
        $trainer = Trainer::findOrFail($id);

        // Ambil semua competency dari relasi pivot
        $competencyDescs = $trainer->competencies->pluck('description')->toArray();

        $this->selectedId = $id;
        $isInternal = !is_null($trainer->user_id);
        $this->formData = [
            'trainer_type' => $isInternal ? 'internal' : 'external',
            'name' => $isInternal ? '' : ($trainer->name ?? ''),
            'user_id' => $trainer->user_id,
            'institution' => $trainer->institution,
            'competencies' => !empty($competencyDescs) ? $competencyDescs : [''],
        ];

        $this->mode = 'edit';
        $this->modal = true;

        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        // Kumpulkan list competency yang diisi (hapus kosong & duplikasi)
        $descs = collect($this->formData['competencies'] ?? [])
            ->map(fn($v) => is_string($v) ? trim($v) : $v)
            ->filter()
            ->unique()
            ->values();

        if ($descs->isEmpty()) {
            $this->addError('formData.competencies', 'Minimal 1 competency harus diisi.');
            return;
        }

        // Buat/ambil ID untuk tiap competency
        $competencyIds = $descs->map(function ($desc) {
            return Competency::firstOrCreate(['description' => $desc])->id;
        })->all();

        // Siapkan data trainer sesuai tipe
        $isInternal = ($this->formData['trainer_type'] ?? 'internal') === 'internal';
        $trainerData = [
            'user_id' => $isInternal ? $this->formData['user_id'] : null,
            'name' => $isInternal ? null : ($this->formData['name'] ?? null),
            'institution' => $this->formData['institution'],
        ];

        if ($this->mode === 'create') {
            $trainer = Trainer::create($trainerData);
            // Simpan relasi ke competency di pivot (banyak)
            $trainer->competencies()->attach($competencyIds);
            $this->success('Berhasil menambahkan data baru', position: 'toast-top toast-center');
        } else {
            $trainer = Trainer::findOrFail($this->selectedId);
            $trainer->update($trainerData);
            // Update relasi competency (sinkron banyak)
            $trainer->competencies()->sync($competencyIds);
            $this->success('Berhasil memperbarui data', position: 'toast-top toast-center');
        }

        $this->modal = false;
    }

    // Tambah baris input competency
    public function addCompetencyRow(): void
    {
        $this->formData['competencies'][] = '';
    }

    // Hapus baris input competency berdasarkan index
    public function removeCompetencyRow(int $index): void
    {
        if (!isset($this->formData['competencies'][$index]))
            return;
        unset($this->formData['competencies'][$index]);
        // rapikan index agar berurutan
        $this->formData['competencies'] = array_values($this->formData['competencies']);
    }

    #[On('deleteTrainer')]
    public function deleteTrainer($id)
    {
        Trainer::findOrFail($id)->delete();

        $this->error('Berhasil menghapus data', position: 'toast-top toast-center');
    }

    public function headers()
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'name', 'label' => 'Trainer Name', 'class' => 'w-[300px]'],
            ['key' => 'institution', 'label' => 'Institution', 'class' => '!text-center'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function trainers()
    {
        $filter = strtolower($this->filter ?? '');

        $query = Trainer::query()
            ->when(
                $this->search,
                fn($q) =>
                $q->where(function ($inner) {
                    $inner->whereHas('user', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    })
                        ->orWhere('trainer.name', 'like', '%' . $this->search . '%')
                        ->orWhere('institution', 'like', '%' . $this->search . '%');
                })
            )
            ->when($filter === 'internal', fn($q) => $q->whereNotNull('trainer.user_id'))
            ->when($filter === 'external', fn($q) => $q->whereNull('trainer.user_id'))
            ->leftJoin('users', 'trainer.user_id', '=', 'users.id')
            ->orderBy('created_at', 'asc')
            ->select('trainer.*');

        $paginator = $query->paginate(10);

        // Attach continuous row numbers so the view doesn't need paginator context inside slots
        return $paginator->through(function ($trainer, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $trainer->no = $start + $index;
            return $trainer;
        });
    }

    public function export()
    {
        return Excel::download(new DataTrainerExport(), 'data_trainers.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new DataTrainerTemplateExport(), 'data_trainer_template.xlsx');
    }

    public function updatedFile()
    {
        if (!$this->file)
            return;

        $this->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $import = new DataTrainerImport();
        Excel::import($import, $this->file);

        $parts = [];
        if (!empty($import->created)) {
            $parts[] = "{$import->created} data ditambahkan";
        }
        if (!empty($import->updated)) {
            $parts[] = "{$import->updated} data diperbarui";
        }
        if (!empty($import->skipped)) {
            $parts[] = "{$import->skipped} data gagal ditambahkan";
        }
        $summary = 'Import selesai.' . (count($parts) ? ' ' . implode(', ', $parts) . '.' : '');
        $this->success($summary, position: 'toast-top toast-center');
        $this->file = null;
    }

    public function render()
    {
        return view('pages.training.data-trainer', [
            'headers' => $this->headers(),
            'trainers' => $this->trainers(),
        ]);
    }
}
