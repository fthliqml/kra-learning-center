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
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;

/**
 * DataTrainer Livewire Component
 *
 * Manages trainer data including internal (employee) and external trainers.
 * Features include CRUD operations, Excel import/export, and duplicate detection.
 */
class DataTrainer extends Component
{
    use Toast, WithPagination, WithFileUploads;
    public $modal = false;
    public $selectedId = null;
    public $mode = 'create'; // create | edit | preview
    public $search = '';
    public $users = [];
    public $file;
    public $filter = '';
    public $trainerNameSearch = '';
    public $filteredUsers = [];
    public $duplicateWarning = '';

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
        $this->reset(['formData', 'selectedId', 'trainerNameSearch', 'filteredUsers', 'duplicateWarning']);
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

    /**
     * Auto-fill institution and clear fields when switching trainer type
     */
    public function updatedFormDataTrainerType($value): void
    {
        if ($value === 'internal') {
            $this->formData['institution'] = 'PT Komatsu Remanufacturing Asia';
            $this->formData['name'] = '';
            $this->trainerNameSearch = '';
            $this->filteredUsers = [];
        } else {
            $this->formData['institution'] = '';
            $this->formData['user_id'] = '';
            $this->trainerNameSearch = '';
            $this->filteredUsers = [];
        }

        $this->duplicateWarning = '';
    }

    /**
     * Handle external trainer name changes to check for duplicates
     */
    public function updatedFormDataName($value): void
    {
        $this->checkDuplicateTrainer();
    }

    /**
     * Handle trainer name search for internal trainers
     */
    public function updatedTrainerNameSearch($value): void
    {
        if (empty($value)) {
            $this->filteredUsers = [];
            $this->formData['user_id'] = '';
            return;
        }

        // Clear selection if search value changes after trainer is selected
        if (!empty($this->formData['user_id'])) {
            $selectedUser = collect($this->users)->firstWhere('value', $this->formData['user_id']);
            if ($selectedUser && $selectedUser['label'] !== $value) {
                $this->formData['user_id'] = '';
            }
        }

        $this->filteredUsers = collect($this->users)
            ->filter(function ($user) use ($value) {
                return stripos($user['label'], $value) !== false;
            })
            ->take(10) // Limit results to 10
            ->values()
            ->toArray();
    }

    /**
     * Select a trainer from the search dropdown
     */
    public function selectTrainer($userId, $userName): void
    {
        $this->formData['user_id'] = $userId;
        $this->trainerNameSearch = $userName;
        $this->filteredUsers = [];

        $this->checkDuplicateTrainer();
    }

    /**
     * Check if trainer already exists and show warning
     */
    public function checkDuplicateTrainer(): void
    {
        if ($this->mode !== 'create') {
            $this->duplicateWarning = '';
            return;
        }

        $isInternal = ($this->formData['trainer_type'] ?? 'internal') === 'internal';
        $existingTrainer = null;

        if ($isInternal && !empty($this->formData['user_id'])) {
            $existingTrainer = Trainer::where('user_id', $this->formData['user_id'])->first();
        } elseif (!$isInternal && !empty($this->formData['name'])) {
            $existingTrainer = Trainer::where('name', $this->formData['name'])
                ->whereNull('user_id')
                ->first();
        }

        if ($existingTrainer) {
            $trainerName = $isInternal ?
                ($existingTrainer->user->name ?? 'Trainer') :
                $existingTrainer->name;
            $this->duplicateWarning = "Trainer \"{$trainerName}\" sudah terdaftar. Data akan diperbarui jika Anda melanjutkan.";
        } else {
            $this->duplicateWarning = '';
        }
    }

    /**
     * Clear search dropdown and selection
     */
    public function clearTrainerSearch(): void
    {
        $this->filteredUsers = [];
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

        // Set trainer name search for internal trainers in edit mode
        if ($isInternal && $trainer->user) {
            $this->trainerNameSearch = $trainer->user->name;
        } else {
            $this->trainerNameSearch = '';
        }

        // Clear filtered users and duplicate warning for edit mode
        $this->filteredUsers = [];
        $this->duplicateWarning = '';

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
            // Check if trainer already exists
            $existingTrainer = null;

            if ($isInternal) {
                // For internal trainer, check by user_id
                $existingTrainer = Trainer::where('user_id', $this->formData['user_id'])->first();
            } else {
                // For external trainer, check by name
                $existingTrainer = Trainer::where('name', $this->formData['name'])
                    ->whereNull('user_id')
                    ->first();
            }

            if ($existingTrainer) {
                // Update existing trainer
                $existingTrainer->update($trainerData);
                $existingTrainer->competencies()->sync($competencyIds);
                $this->success('Data trainer sudah ada, berhasil memperbarui data yang sudah ada', position: 'toast-top toast-center');
            } else {
                // Create new trainer
                $trainer = Trainer::create($trainerData);
                $trainer->competencies()->attach($competencyIds);
                $this->success('Berhasil menambahkan data baru', position: 'toast-top toast-center');
            }
        } else {
            $trainer = Trainer::findOrFail($this->selectedId);
            $trainer->update($trainerData);
            // Update relasi competency (sinkron banyak)
            $trainer->competencies()->sync($competencyIds);
            $this->success('Berhasil memperbarui data', position: 'toast-top toast-center');
        }

        $this->modal = false;
    }

    /**
     * Add new competency input row
     */
    public function addCompetencyRow(): void
    {
        $this->formData['competencies'][] = '';
    }

    /**
     * Remove competency input row by index
     */
    public function removeCompetencyRow(int $index): void
    {
        if (!isset($this->formData['competencies'][$index]))
            return;
        unset($this->formData['competencies'][$index]);
        $this->formData['competencies'] = array_values($this->formData['competencies']);
    }

    #[On('deleteTrainer')]
    public function deleteTrainer($id)
    {
        try {
            $trainer = Trainer::findOrFail($id);
            $trainerName = $trainer->user_id ?
                ($trainer->user->name ?? 'Trainer') :
                $trainer->name;

            $trainer->delete();

            $this->success("Data trainer \"{$trainerName}\" berhasil dihapus", position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Gagal menghapus data trainer: ' . $e->getMessage(), position: 'toast-top toast-center');
        }
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
        try {
            $this->success('Memproses export data trainer...', position: 'toast-top toast-center');
            return Excel::download(new DataTrainerExport(), 'data_trainers_' . date('Y-m-d_H-i-s') . '.xlsx');
        } catch (\Exception $e) {
            $this->error('Gagal mengexport data trainer. Silakan coba lagi.', position: 'toast-top toast-center');
        }
    }

    public function downloadTemplate()
    {
        try {
            $this->success('Mendownload template Excel...', position: 'toast-top toast-center');
            return Excel::download(new DataTrainerTemplateExport(), 'template_data_trainer_' . date('Y-m-d') . '.xlsx');
        } catch (\Exception $e) {
            $this->error('Gagal mendownload template. Silakan coba lagi.', position: 'toast-top toast-center');
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

            $this->success('Memproses import data trainer...', position: 'toast-top toast-center');

            $import = new DataTrainerImport();
            Excel::import($import, $this->file);

            // Build summary message
            $parts = [];
            $totalProcessed = $import->created + $import->updated + $import->skipped;

            if (!empty($import->created)) {
                $parts[] = "{$import->created} data trainer baru ditambahkan";
            }
            if (!empty($import->updated)) {
                $parts[] = "{$import->updated} data trainer diperbarui";
            }
            if (!empty($import->skipped)) {
                $parts[] = "{$import->skipped} data trainer gagal diproses";
            }

            if ($totalProcessed === 0) {
                $this->warning('Tidak ada data yang diproses. Pastikan file Excel memiliki format yang benar.', position: 'toast-top toast-center');
            } elseif ($import->skipped > 0 && ($import->created + $import->updated) === 0) {
                $this->error('Import gagal! Semua data tidak dapat diproses. Periksa format data dalam file Excel.', position: 'toast-top toast-center');
            } elseif ($import->skipped > 0) {
                $summary = 'Import selesai dengan peringatan: ' . implode(', ', $parts);
                $this->warning($summary, position: 'toast-top toast-center');
            } else {
                $summary = 'Import berhasil! ' . implode(', ', $parts);
                $this->success($summary, position: 'toast-top toast-center');
            }

            $this->file = null;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error('File yang diupload tidak valid. Gunakan file Excel (.xlsx atau .xls)', position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan saat import data: ' . $e->getMessage(), position: 'toast-top toast-center');
            $this->file = null;
        }
    }

    public function render()
    {
        return view('pages.training.data-trainer', [
            'headers' => $this->headers(),
            'trainers' => $this->trainers(),
        ]);
    }
}
