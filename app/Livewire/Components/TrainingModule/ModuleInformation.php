<?php

namespace App\Livewire\Components\TrainingModule;

use App\Models\Competency;
use App\Models\TrainingModule;
use Livewire\Component;
use Mary\Traits\Toast;

class ModuleInformation extends Component
{
  use Toast;

  public array $formData = [
    'title' => '',
    'competency_id' => '',
    'objective' => '',
    'training_content' => '',
    'method' => '',
    'duration' => '',
    'frequency' => '',
    'theory_passing_score' => '75',
    'practical_passing_score' => '75',
  ];

  public ?int $moduleId = null;
  public array $competencyOptions = [];

  protected string $originalHash = '';
  public bool $isDirty = false;
  public bool $hasEverSaved = false;
  public bool $persisted = false;

  protected $rules = [
    'formData.title' => 'required|string|max:255',
    'formData.competency_id' => 'required|exists:competency,id',
    'formData.objective' => 'required|string',
    'formData.training_content' => 'required|string',
    'formData.method' => 'required|string|max:255',
    'formData.duration' => 'required|numeric|min:1',
    'formData.frequency' => 'required|integer|min:1',
    'formData.theory_passing_score' => 'required|numeric|min:0|max:100',
    'formData.practical_passing_score' => 'required|numeric|min:0|max:100',
  ];

  protected $messages = [
    'formData.title.required' => 'Title is required.',
    'formData.competency_id.required' => 'Competency is required.',
    'formData.objective.required' => 'Objective is required.',
    'formData.training_content.required' => 'Training content is required.',
    'formData.method.required' => 'Method is required.',
    'formData.duration.required' => 'Duration is required.',
    'formData.frequency.required' => 'Frequency is required.',
    'formData.theory_passing_score.required' => 'Theory passing score is required.',
    'formData.practical_passing_score.required' => 'Practical passing score is required.',
  ];

  public function mount(): void
  {
    // Load competency options
    $this->loadCompetencyOptions();

    if ($this->moduleId) {
      $module = TrainingModule::with('competency')->find($this->moduleId);
      if ($module) {
        $this->formData = [
          'title' => $module->title ?? '',
          'competency_id' => $module->competency_id ?? '',
          'objective' => $module->objective ?? '',
          'training_content' => $module->training_content ?? '',
          'method' => $module->method ?? '',
          'duration' => $module->duration ?? '',
          'frequency' => $module->frequency ?? '',
          'theory_passing_score' => $module->theory_passing_score ?? '75',
          'practical_passing_score' => $module->practical_passing_score ?? '75',
        ];
        $this->hasEverSaved = true;
        $this->persisted = true;

        // Ensure selected competency is in options
        if ($module->competency && !collect($this->competencyOptions)->contains('value', $module->competency_id)) {
          array_unshift($this->competencyOptions, [
            'value' => $module->competency->id,
            'label' => "[{$module->competency->code}] {$module->competency->name}",
          ]);
        }
      }
    }
    $this->snapshot();
  }

  /**
   * Load initial competency options
   */
  public function loadCompetencyOptions(): void
  {
    $this->competencyOptions = Competency::orderBy('code')
      ->limit(50)
      ->get()
      ->map(fn($c) => ['value' => $c->id, 'label' => "[{$c->code}] {$c->name}"])
      ->toArray();
  }

  public function competencySearch(string $value = ''): void
  {
    $this->competencyOptions = Competency::query()
      ->when($value, fn($q) => $q->where('name', 'like', "%{$value}%")
        ->orWhere('code', 'like', "%{$value}%"))
      ->orderBy('code')
      ->limit(50)
      ->get()
      ->map(fn($c) => ['value' => $c->id, 'label' => "[{$c->code}] {$c->name}"])
      ->toArray();
  }

  public function updated($property): void
  {
    if (str_starts_with($property, 'formData')) {
      $this->computeDirty();
    }
  }

  protected function snapshot(): void
  {
    $this->originalHash = md5(json_encode($this->formData));
    $this->isDirty = false;
  }

  protected function computeDirty(): void
  {
    $this->isDirty = md5(json_encode($this->formData)) !== $this->originalHash;
    if ($this->isDirty) {
      $this->persisted = false;
    }
  }

  public function saveDraft(): void
  {
    $this->validate();

    $module = TrainingModule::updateOrCreate(
      ['id' => $this->moduleId],
      $this->formData
    );

    if (!$this->moduleId) {
      $this->moduleId = $module->id;
      $this->dispatch('moduleCreated', $module->id);
    }

    $this->snapshot();
    $this->hasEverSaved = true;
    $this->persisted = true;

    $this->success('Module information saved successfully!', position: 'toast-top toast-center');
  }

  public function goNext(): void
  {
    $this->dispatch('setTab', 'pretest');
  }

  public function goBack(): mixed
  {
    return redirect()->route('training-module.index');
  }

  public function placeholder()
  {
    return view('components.skeletons.module-information');
  }

  public function render()
  {
    return view('components.training-module.module-information');
  }
}
