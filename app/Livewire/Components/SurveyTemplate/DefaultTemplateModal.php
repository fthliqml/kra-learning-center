<?php

namespace App\Livewire\Components\SurveyTemplate;

use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateDefault;
use Livewire\Component;
use Mary\Traits\Toast;

class DefaultTemplateModal extends Component
{
  use Toast;

  public bool $modal = false;
  public int $level = 1;

  // Removed groupCompOptions

  public array $levelOptions = [
    ['value' => 1, 'label' => 'Level 1 - Reaction (Immediately after training)'],
    ['value' => 3, 'label' => 'Level 3 - Behavior (3 months after training)'],
  ];

  // Default template id for selected level
  public $defaultTemplateId = null;

  protected $listeners = [
    'open-default-template-modal' => 'openModal',
  ];

  public function openModal(): void
  {
    $this->loadDefault();
    $this->modal = true;
  }

  public function loadDefault(): void
  {
    $default = SurveyTemplateDefault::where('level', $this->level)->first();
    $this->defaultTemplateId = $default?->survey_template_id;
  }

  public function updatedLevel(): void
  {
    $this->loadDefault();
  }

  public function getTemplateOptionsProperty(): array
  {
    return SurveyTemplate::where('level', $this->level)
      ->where('status', 'active')
      ->orderBy('title')
      ->get()
      ->map(fn($t) => [
        'value' => $t->id,
        'label' => $t->title,
      ])
      ->prepend(['value' => null, 'label' => '-- No Default --'])
      ->toArray();
  }

  public function save(): void
  {
    try {
      // Clear all defaults for this level first
      SurveyTemplateDefault::where('level', $this->level)->delete();

      // Create new default
      if ($this->defaultTemplateId) {
        SurveyTemplateDefault::setDefaultForLevel($this->defaultTemplateId, $this->level);
      }

      $this->success('Default template saved successfully.', position: 'toast-top toast-center');
      $this->modal = false;
    } catch (\Throwable $e) {
      $this->error('Failed to save default: ' . $e->getMessage(), position: 'toast-top toast-center');
    }
  }

  public function render()
  {
    return view('components.survey-template.default-template-modal', [
      'templateOptions' => $this->templateOptions,
    ]);
  }
}
