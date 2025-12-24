<?php

namespace App\Livewire\Components;

use App\Models\InstructorDailyRecord;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class InstructorDailyRecordModal extends Component
{
  use Toast;

  public bool $showModal = false;
  public bool $isEdit = false;
  public ?int $recordId = null;

  // Form fields
  public $date = null;
  public $instructor_id = null;

  // Fixed values
  public int $attendanceSpl = 450; // 7.5 hours in minutes

  // Calculated fields (readonly)
  public int $recordUtilization = 0;
  public int $available = 450;

  // Job items array
  public array $jobItems = [];

  // Job description options
  public array $jobDescOptions = [
    ['value' => '101', 'label' => '101 - Formal Teaching'],
    ['value' => '102', 'label' => '102 - Non Formal Teaching'],
    ['value' => '201', 'label' => '201 - Formal Development'],
    ['value' => '202', 'label' => '202 - Non Formal Development'],
    ['value' => '301', 'label' => '301 - Develop Training Aid'],
    ['value' => '302', 'label' => '302 - Prepare/Report'],
    ['value' => '303', 'label' => '303 - Observasi & Konsultasi Teknis'],
    ['value' => '304', 'label' => '304 - Project/Job Assignment'],
    ['value' => '305', 'label' => '305 - Meeting'],
    ['value' => '306', 'label' => '306 - Travel'],
    ['value' => '400', 'label' => '400 - Others'],
  ];

  protected $rules = [
    'date' => 'required|date',
    'instructor_id' => 'required|exists:users,id',
    'jobItems' => 'required|array|min:1',
    'jobItems.*.job_desc' => 'required|string',
    'jobItems.*.remark' => 'nullable|string|max:255',
    'jobItems.*.manhour' => 'required|numeric|min:1|max:450',
  ];

  protected $messages = [
    'date.required' => 'Date is required.',
    'instructor_id.required' => 'Instructor is required.',
    'jobItems.required' => 'At least one job item is required.',
    'jobItems.min' => 'At least one job item is required.',
    'jobItems.*.job_desc.required' => 'Job description is required.',
    'jobItems.*.manhour.required' => 'Manhour is required.',
    'jobItems.*.manhour.min' => 'Manhour must be at least 1 minute.',
    'jobItems.*.manhour.max' => 'Manhour cannot exceed 450 minutes.',
  ];

  public function mount(): void
  {
    $this->resetForm();
  }

  public function resetForm(): void
  {
    $this->isEdit = false;
    $this->recordId = null;
    $this->date = now()->format('Y-m-d');
    $this->instructor_id = Auth::id();
    $this->recordUtilization = 0;
    $this->available = $this->attendanceSpl;
    $this->jobItems = [
      ['job_desc' => '', 'remark' => '', 'manhour' => null],
    ];
  }

  #[On('open-daily-record-modal')]
  public function openModal(?int $id = null): void
  {
    $this->resetForm();
    $this->resetValidation();

    if ($id) {
      $this->loadRecord($id);
    }

    $this->showModal = true;
  }

  public function loadRecord(int $id): void
  {
    $record = InstructorDailyRecord::find($id);

    if (!$record) {
      $this->error('Record not found.');
      return;
    }

    $this->isEdit = true;
    $this->recordId = $id;
    $this->date = $record->date->format('Y-m-d');
    $this->instructor_id = $record->instructor_id;

    $this->jobItems = [
      [
        'job_desc' => $record->code,
        'remark' => $record->remarks ?? '',
        'manhour' => (int) ($record->hour * 60), // Convert hours to minutes
      ],
    ];

    $this->calculateTotals();
  }

  public function addRow(): void
  {
    $this->jobItems[] = ['job_desc' => '', 'remark' => '', 'manhour' => null];
  }

  public function removeRow(int $index): void
  {
    if (count($this->jobItems) > 1) {
      unset($this->jobItems[$index]);
      $this->jobItems = array_values($this->jobItems);
      $this->calculateTotals();
    }
  }

  public function updatedJobItems(): void
  {
    $this->calculateTotals();
  }

  public function calculateTotals(): void
  {
    $totalManhour = 0;
    foreach ($this->jobItems as $item) {
      $totalManhour += (int) ($item['manhour'] ?? 0);
    }

    $this->recordUtilization = $totalManhour;
    $this->available = max(0, $this->attendanceSpl - $totalManhour);
  }

  public function closeModal(): void
  {
    $this->showModal = false;
    $this->resetForm();
  }

  public function save(): void
  {
    $this->validate();

    // Check if total manhour exceeds attendance
    if ($this->recordUtilization > $this->attendanceSpl) {
      $this->error('Total manhour cannot exceed attendance + SPL (' . $this->attendanceSpl . ' minutes).');
      return;
    }

    // Filter out empty job items
    $validItems = array_filter($this->jobItems, fn($item) => !empty($item['job_desc']) && !empty($item['manhour']));

    if (empty($validItems)) {
      $this->error('At least one job item with job description and manhour is required.');
      return;
    }

    // Save each job item as a separate record
    foreach ($validItems as $item) {
      $activityLabel = $this->getJobDescLabel($item['job_desc']);
      // Remove the code prefix from the label (e.g., "101 - Formal Teaching" -> "Formal Teaching")
      $activityName = preg_replace('/^\d+\s-\s/', '', $activityLabel);

      $data = [
        'instructor_id' => $this->instructor_id,
        'date' => $this->date,
        'code' => $item['job_desc'],
        'activity' => $activityName,
        'remarks' => $item['remark'] ?? null,
        'hour' => round((int) $item['manhour'] / 60, 1), // Convert minutes to hours
      ];

      if ($this->isEdit && $this->recordId && count($validItems) === 1) {
        // Update existing record (only if single item)
        $record = InstructorDailyRecord::find($this->recordId);
        if ($record) {
          $record->update($data);
        }
      } else {
        // Create new record(s)
        InstructorDailyRecord::create($data);
      }
    }

    $this->success($this->isEdit ? 'Record updated successfully.' : 'Record(s) created successfully.', position: 'toast-top toast-center');
    $this->closeModal();
    $this->dispatch('record-saved');
  }

  /**
   * Get job description label from code
   */
  protected function getJobDescLabel(string $code): string
  {
    foreach ($this->jobDescOptions as $option) {
      if ($option['value'] === $code) {
        return $option['label'];
      }
    }
    return $code;
  }

  /**
   * Get instructor display text (NRP - Name)
   */
  public function getInstructorDisplayProperty(): string
  {
    $user = Auth::user();
    return $user->nrp . ' - ' . $user->name;
  }

  public function render()
  {
    return view('livewire.components.instructor-daily-record-modal', [
      'instructorDisplay' => $this->instructorDisplay,
    ]);
  }
}
