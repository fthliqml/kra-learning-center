<?php

namespace App\Livewire\Pages\Training;

use App\Models\TrainingModule;
use Livewire\Component;

class ModuleEdit extends Component
{
  public ?TrainingModule $module = null;
  public bool $isCreating = false;
  public ?int $moduleId = null;

  public string $activeTab = 'module-info';

  protected $listeners = [
    'setTab' => 'setTab',
    'moduleCreated' => 'onModuleCreated',
  ];

  public function mount(?TrainingModule $module = null): void
  {
    if ($module && $module->exists) {
      $this->module = $module;
      $this->isCreating = false;
      $this->moduleId = $module->id;
    } else {
      $this->module = new TrainingModule();
      $this->isCreating = true;
      $this->activeTab = 'module-info';
      $this->moduleId = null;
    }
  }

  public function setTab(string $to): void
  {
    if ($this->isCreating && !$this->moduleId && $to !== 'module-info') {
      return;
    }
    $this->activeTab = $to;
  }

  public function onModuleCreated(int $newId): void
  {
    if ($this->isCreating) {
      $this->module = TrainingModule::find($newId) ?? $this->module;
      $this->moduleId = $newId;
      $this->isCreating = false;
    }
  }

  public function render()
  {
    return view('pages.training.module-edit');
  }
}
