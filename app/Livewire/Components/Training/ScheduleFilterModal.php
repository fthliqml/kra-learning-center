<?php

namespace App\Livewire\Components\Training;

use App\Models\Trainer;
use Livewire\Component;

class ScheduleFilterModal extends Component
{
  public bool $open = false;
  public $trainerId = null;
  public $type = null; // training type
  public array $trainerOptions = [];

  protected $listeners = [
    'open-schedule-filter' => 'openModal',
    'schedule-month-context' => 'onMonthContext',
    'schedule-clear-all' => 'externalClearAll',
    'schedule-clear-trainer' => 'externalClearTrainer',
    'schedule-clear-type' => 'externalClearType',
  ];

  public function mount(): void
  {
    $this->loadTrainerOptions();
  }

  public function openModal(): void
  {
    $this->open = true;
  }

  public function closeModal(): void
  {
    $this->open = false;
  }

  public function loadTrainerOptions(): void
  {
    $collection = Trainer::with('user')
      ->get()
      ->map(function ($t) {
        $label = $t->name ?: ($t->user->name ?? 'Trainer #' . $t->id);
        return [
          'value' => $t->id,
          'label' => $label,
          'sort' => strtolower($label),
        ];
      })
      ->sortBy('sort')
      ->values();
    $this->trainerOptions = $collection->map(fn($r) => [
      'value' => $r['value'],
      'label' => $r['label'],
    ])->toArray();
  }

  public function resetFilters(): void
  {
    $this->trainerId = null;
    $this->type = null;
    $this->dispatch('schedule-filters-updated', trainerId: null, type: null);
  }

  public function apply(): void
  {
    $this->dispatch('schedule-filters-updated', trainerId: $this->trainerId ?: null, type: $this->type ?: null);
    $this->closeModal();
  }

  public function externalClearAll(): void
  {
    // Called when Clear All button outside modal is clicked; sync internal state so select inputs reset next open
    $this->trainerId = null;
    $this->type = null;
  }

  public function externalClearTrainer(): void
  {
    $this->trainerId = null;
  }

  public function externalClearType(): void
  {
    $this->type = null;
  }

  public function onMonthContext(): void
  {
    // placeholder if context-specific filtering later needed
  }

  public function render()
  {
    return view('components.training.schedule-filter-modal');
  }
}
