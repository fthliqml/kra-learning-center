<?php

namespace App\Livewire\Pages\Training;

use App\Models\Competency;
use App\Models\Trainer;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class DataTrainer extends Component
{
    use Toast, WithPagination;
    public $modal = false;
    public $selectedId = null;
    public $mode = 'create'; //  create | edit | preview
    public $search = '';
    public $users = [];

    public function mount()
    {
        $this->users = User::all()->map(function ($user) {
            return [
                'value' => $user->id,
                'label' => $user->name,
            ];
        })->toArray();
    }

    public $formData = [
        // 'name' => '',
        'user_id' => '',
        'institution' => '',
        'competencies' => [''],
    ];

    protected $rules = [
        // 'formData.name' => 'required|string|max:255',
        'formData.user_id' => 'required',
        'formData.institution' => 'required|string',
        'formData.competencies' => 'required|array|min:1',
        'formData.competencies.*' => 'nullable|string|max:255',
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
        $trainer = Trainer::findOrFail($id);

        // Ambil semua competency dari relasi pivot
        $competencyDescs = $trainer->competencies->pluck('description')->toArray();

        $this->selectedId = $id;
        $this->formData = [
            'name' => $trainer->user->name ?? '',
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
        $this->formData = [
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

        // Simpan trainer
        $trainerData = [
            'user_id' => $this->formData['user_id'],
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
        if (!isset($this->formData['competencies'][$index])) return;
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
        $query = Trainer::query()
            ->when(
                $this->search,
                fn($q) =>
                $q->whereHas('user', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                    ->orWhere('institution', 'like', '%' . $this->search . '%')
            )
            ->join('users', 'trainer.user_id', '=', 'users.id')
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

    public function render()
    {
        return view('livewire.pages.training.data-trainer', [
            'headers' => $this->headers(),
            'trainers' => $this->trainers(),
        ]);
    }
}
