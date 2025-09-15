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
        'competency' => '',
    ];

    protected $rules = [
        // 'formData.name' => 'required|string|max:255',
        'formData.user_id' => 'required',
        'formData.institution' => 'required|string',
        'formData.competency' => 'nullable|string',
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

        // Ambil competency dari relasi pivot (ambil satu saja jika ada)
        $competencyDesc = $trainer->competencies->first()->description ?? '';

        $this->selectedId = $id;
        $this->formData = [
            'name' => $trainer->user->name ?? '',
            'user_id' => $trainer->user_id,
            'institution' => $trainer->institution,
            'competency' => $competencyDesc,
        ];

        $this->mode = 'preview';
        $this->modal = true;

        $this->resetValidation();
    }

    public function openEditModal($id)
    {
        $trainer = Trainer::findOrFail($id);

        // Ambil competency dari relasi pivot (ambil satu saja jika ada)
        $competencyDesc = $trainer->competencies->first()->description ?? '';

        $this->selectedId = $id;
        $this->formData = [
            'user_id' => $trainer->user_id,
            'institution' => $trainer->institution,
            'competency' => $competencyDesc,
        ];

        $this->mode = 'edit';
        $this->modal = true;

        $this->resetValidation();
    }

    public function save()
    {

        $this->validate([
            'formData.user_id' => 'required',
            'formData.institution' => 'required|string',
            'formData.competency' => 'required|string',
        ]);

        // Simpan competency ke tabel competency jika belum ada
        $competencyDesc = $this->formData['competency'];
        $competency = Competency::firstOrCreate(['description' => $competencyDesc]);

        // Simpan trainer
        $trainerData = [
            'user_id' => $this->formData['user_id'],
            'institution' => $this->formData['institution'],
        ];

        if ($this->mode === 'create') {
            $trainer = \App\Models\Trainer::create($trainerData);
            // Simpan relasi ke competency di pivot
            $trainer->competencies()->attach($competency->id);
            $this->success('Berhasil menambahkan data baru', position: 'toast-top toast-center');
        } else {
            $trainer = \App\Models\Trainer::findOrFail($this->selectedId);
            $trainer->update($trainerData);
            // Update relasi competency
            $trainer->competencies()->sync([$competency->id]);
            $this->success('Berhasil memperbarui data', position: 'toast-top toast-center');
        }

        $this->modal = false;
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
        return Trainer::query()
            ->when(
                $this->search,
                fn($q) =>
                $q->whereHas('user', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                    ->orWhere('institution', 'like', '%' . $this->search . '%')
            )
            ->join('users', 'trainer.user_id', '=', 'users.id')
            ->orderBy('users.name', 'asc')
            ->select('trainer.*')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.pages.training.data-trainer', [
            'headers' => $this->headers(),
            'trainers' => $this->trainers(),
        ]);
    }
}
