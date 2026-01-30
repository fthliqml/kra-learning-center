<?php

namespace App\Livewire\Pages\Training;

use App\Exports\DataTrainerExport;
use App\Exports\DataTrainerTemplateExport;
use App\Imports\DataTrainerImport;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
  public $mode = 'create';
  public $search = '';
  public $users = [];
  public $file;
  public $filter = '';
  public $duplicateWarning = '';
  public array $trainersSearchable = [];
  public $signatureFile;

  public function mount()
  {
    $this->users = User::all()->map(function ($user) {
      return [
        'value' => $user->id,
        'label' => $user->name,
      ];
    })->toArray();

    $this->trainersSearchable = collect($this->users)
      ->take(15)
      ->map(fn($u) => ['id' => $u['value'], 'name' => $u['label']])
      ->values()
      ->all();
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
    'signature_path' => null,
    'signature_exists' => false,
  ];

  protected function rules()
  {
    $isInternal = ($this->formData['trainer_type'] ?? 'internal') === 'internal';
    return [
      'formData.trainer_type' => 'required|in:internal,external',
      'formData.user_id' => [$isInternal ? 'required' : 'nullable'],
      'formData.name' => [$isInternal ? 'nullable' : 'required', 'string', 'max:255'],
      'formData.institution' => 'required|string',
      'signatureFile' => ['nullable', 'image', 'max:2048'],
    ];
  }

  /**
   * Custom validation messages (English)
   */
  protected function messages(): array
  {
    return [
      'formData.trainer_type.required' => 'Please select a trainer type.',
      'formData.trainer_type.in' => 'Trainer type must be Internal or External.',
      'formData.user_id.required' => 'Please choose an internal trainer.',
      'formData.name.required' => 'Trainer name is required.',
      'formData.name.max' => 'Trainer name may not exceed 255 characters.',
      'formData.institution.required' => 'Institution is required.',
      'formData.competencies.required' => 'Select at least one competency.',
      'formData.competencies.array' => 'Invalid competencies data.',
      'formData.competencies.min' => 'Select at least one competency.',
      'formData.competencies.*.exists' => 'Selected competency is invalid.',
    ];
  }

  /**
   * Friendly attribute names (avoid `formData.xyz` in messages)
   */
  protected function validationAttributes(): array
  {
    return [
      'formData.trainer_type' => 'trainer type',
      'formData.user_id' => 'internal trainer',
      'formData.name' => 'trainer name',
      'formData.institution' => 'institution',
      'signatureFile' => 'signature',
    ];
  }

  public function updated($property): void
  {
    if (!is_array($property) && $property != "") {
      $this->resetPage();
    }
  }

  /**
   * Determine if the current user can view a trainer's signature.
   * - Internal trainer (has user_id): only that user can view.
   * - External trainer (no user_id): only admin role can view.
   */
  protected function canViewSignature(Trainer $trainer): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    // Internal trainer: only the linked user can view their signature
    if ($trainer->user_id) {
      return $user->id === $trainer->user_id;
    }

    // External trainer: only admin functional role can view
    return $user->hasRole('admin');
  }

  /**
   * Determine if the current user can manage (edit/delete) a trainer record/signature.
   * - Internal trainer: only the linked user.
   * - External trainer: only admin.
   */
  protected function canManageTrainer(Trainer $trainer): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    if ($trainer->user_id) {
      return $user->id === $trainer->user_id;
    }

    return $user->hasRole('admin');
  }

  /**
   * Determine if the current user can delete a trainer record.
   * - Admin can delete any trainer (internal/external)
   * - Otherwise follows existing manage rules
   */
  protected function canDeleteTrainer(Trainer $trainer): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    if ($user->hasRole('admin')) {
      return true;
    }

    return $this->canManageTrainer($trainer);
  }

  public function openCreateModal()
  {
    $this->reset(['formData', 'selectedId', 'duplicateWarning']);
    $this->formData = [
      'trainer_type' => '',
      'name' => '',
      'user_id' => '',
      'institution' => '',
      'signature_path' => null,
      'signature_exists' => false,
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
    } else {
      $this->formData['institution'] = '';
      $this->formData['user_id'] = '';
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
      $this->duplicateWarning = "Trainer \"{$trainerName}\" is already registered. Existing data will be updated if you continue.";
    } else {
      $this->duplicateWarning = '';
    }
  }

  public function updatedFormDataUserId($value): void
  {
    $this->checkDuplicateTrainer();
  }

  /**
   * Live search for internal trainers
   * @param string $query
   * @return void
   */
  public function trainerSearch(string $query = ''): void
  {
    $query = trim($query);
    if ($query === '') {
      $this->trainersSearchable = collect($this->users)
        ->take(15)
        ->map(fn($u) => ['id' => $u['value'], 'name' => $u['label']])
        ->values()
        ->all();
      return;
    }

    $this->trainersSearchable = collect($this->users)
      ->filter(fn($u) => stripos($u['label'], $query) !== false)
      ->take(15)
      ->map(fn($u) => ['id' => $u['value'], 'name' => $u['label']])
      ->values()
      ->all();
  }

  public function openDetailModal($id)
  {
    $trainer = Trainer::findOrFail($id);

    $this->selectedId = $id;
    $isInternal = !is_null($trainer->user_id);
    $hasSignature = !empty($trainer->signature_path);
    $signaturePath = $this->canViewSignature($trainer) ? $trainer->signature_path : null;

    $this->formData = [
      'trainer_type' => $isInternal ? 'internal' : 'external',
      'name' => $isInternal ? ($trainer->user->name ?? '') : ($trainer->name ?? ''),
      'user_id' => $trainer->user_id,
      'institution' => $trainer->institution,
      'signature_path' => $signaturePath,
      'signature_exists' => $hasSignature,
    ];

    $this->mode = 'preview';
    $this->modal = true;

    if (!is_null($trainer->user_id) && $trainer->user) {
      $exists = collect($this->trainersSearchable)->firstWhere('id', $trainer->user_id);
      if (!$exists) {
        $this->trainersSearchable = array_merge([
          ['id' => $trainer->user_id, 'name' => $trainer->user->name]
        ], $this->trainersSearchable);
      }
    }

    $this->resetValidation();
  }

  public function openEditModal($id)
  {
    $trainer = Trainer::findOrFail($id);

    if (!$this->canManageTrainer($trainer)) {
      $this->error('You are not allowed to edit this trainer.', position: 'toast-top toast-center');
      return;
    }

    $this->selectedId = $id;
    $isInternal = !is_null($trainer->user_id);
    $this->formData = [
      'trainer_type' => $isInternal ? 'internal' : 'external',
      'name' => $isInternal ? '' : ($trainer->name ?? ''),
      'user_id' => $trainer->user_id,
      'institution' => $trainer->institution,
      'signature_path' => $trainer->signature_path,
      'signature_exists' => !empty($trainer->signature_path),
    ];

    $this->duplicateWarning = '';

    $this->mode = 'edit';
    $this->modal = true;

    if (!is_null($trainer->user_id) && $trainer->user) {
      $exists = collect($this->trainersSearchable)->firstWhere('id', $trainer->user_id);
      if (!$exists) {
        $this->trainersSearchable = array_merge([
          ['id' => $trainer->user_id, 'name' => $trainer->user->name]
        ], $this->trainersSearchable);
      }
    }

    $this->resetValidation();
  }

  public function save()
  {
    $this->validate();

    $isInternal = ($this->formData['trainer_type'] ?? 'internal') === 'internal';
    $trainerData = [
      'user_id' => $isInternal ? $this->formData['user_id'] : null,
      'name' => $isInternal ? null : ($this->formData['name'] ?? null),
      'institution' => $this->formData['institution'],
    ];

    // Handle signature upload if provided
    if ($this->signatureFile) {
      $path = $this->signatureFile->store('trainer-signatures', 'public');
      $trainerData['signature_path'] = $path;
    }

    if ($this->mode === 'create') {
      $existingTrainer = null;

      if ($isInternal) {
        $existingTrainer = Trainer::where('user_id', $this->formData['user_id'])->first();
      } else {
        $existingTrainer = Trainer::where('name', $this->formData['name'])
          ->whereNull('user_id')
          ->first();
      }

      if ($existingTrainer) {
        $existingTrainer->update($trainerData);
        $this->success('Trainer already exists. Successfully updated existing data.', position: 'toast-top toast-center');
      } else {
        $trainer = Trainer::create($trainerData);
        $this->success('Successfully added new trainer.', position: 'toast-top toast-center');
      }
    } else {
      $trainer = Trainer::findOrFail($this->selectedId);

      if (!$this->canManageTrainer($trainer)) {
        $this->error('You are not allowed to update this trainer.', position: 'toast-top toast-center');
        return;
      }

      $trainer->update($trainerData);
      $this->success('Trainer data updated successfully.', position: 'toast-top toast-center');
    }

    // Reset signature file after save
    $this->signatureFile = null;

    $this->modal = false;
  }

  /**
   * Explicit close method to be called from front-end (Alpine / buttons)
   */
  public function closeModal(): void
  {
    $this->modal = false;
  }

  #[On('deleteTrainer')]
  public function deleteTrainer($id)
  {
    try {
      $trainer = Trainer::findOrFail($id);

      if (!$this->canDeleteTrainer($trainer)) {
        $this->error('You are not allowed to delete this trainer.', position: 'toast-top toast-center');
        $this->dispatch('confirm-done');
        return;
      }
      $trainerName = $trainer->user_id ?
        ($trainer->user->name ?? 'Trainer') :
        $trainer->name;

      $trainer->delete();

      $this->success("Trainer \"{$trainerName}\" deleted successfully", position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
    } catch (\Exception $e) {
      $this->error('Failed to delete trainer: ' . $e->getMessage(), position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
    }
  }

  #[On('deleteTrainerSignature')]
  public function deleteTrainerSignature(): void
  {
    if (!$this->selectedId) {
      $this->error('No trainer selected.', position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
      return;
    }

    $trainer = Trainer::find($this->selectedId);

    if (!$trainer) {
      $this->error('Trainer not found.', position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
      return;
    }

    if (!$this->canManageTrainer($trainer)) {
      $this->error('You are not allowed to delete this signature.', position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
      return;
    }

    if (empty($trainer->signature_path)) {
      $this->error('No signature to delete.', position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
      return;
    }

    // Delete file from storage
    if (\Storage::disk('public')->exists($trainer->signature_path)) {
      \Storage::disk('public')->delete($trainer->signature_path);
    }

    // Clear signature path in database
    $trainer->update(['signature_path' => null]);

    // Update formData to reflect the change
    $this->formData['signature_path'] = null;
    $this->formData['signature_exists'] = false;

    $this->dispatch('confirm-done');
    $this->success('Signature deleted successfully.', position: 'toast-top toast-center');
  }

  public function headers()
  {
    return [
      ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
      ['key' => 'name', 'label' => 'Trainer Name', 'class' => 'w-[260px]'],
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

    return $paginator->through(function ($trainer, $index) use ($paginator) {
      $start = $paginator->firstItem() ?? 0;
      $trainer->no = $start + $index;
      return $trainer;
    });
  }

  public function export()
  {
    try {
      $this->success('Processing trainer data export...', position: 'toast-top toast-center');
      return Excel::download(new DataTrainerExport(), 'data_trainers_' . date('Y-m-d_H-i-s') . '.xlsx');
    } catch (\Exception $e) {
      $this->error('Failed to export trainer data. Please try again.', position: 'toast-top toast-center');
    }
  }

  public function downloadTemplate()
  {
    try {
      $this->success('Downloading Excel template...', position: 'toast-top toast-center');
      return Excel::download(new DataTrainerTemplateExport(), 'template_data_trainer_' . date('Y-m-d') . '.xlsx');
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

      $this->success('Processing trainer data import...', position: 'toast-top toast-center');

      $import = new DataTrainerImport();
      Excel::import($import, $this->file);

      // Build summary message
      $parts = [];
      $totalProcessed = $import->created + $import->updated + $import->skipped;

      if (!empty($import->created)) {
        $parts[] = "{$import->created} new trainer(s) added";
      }
      if (!empty($import->updated)) {
        $parts[] = "{$import->updated} trainer(s) updated";
      }
      if (!empty($import->skipped)) {
        $parts[] = "{$import->skipped} trainer row(s) failed";
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
      $this->error('File yang diupload tidak valid. Gunakan file Excel (.xlsx atau .xls)', position: 'toast-top toast-center');
    } catch (\Exception $e) {
      $this->error('Error occurred while importing: ' . $e->getMessage(), position: 'toast-top toast-center');
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
