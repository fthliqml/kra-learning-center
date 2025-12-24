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

  public array $groupCompOptions = [
    ['value' => 'BMC', 'label' => 'BMC - Basic Management Competency'],
    ['value' => 'BC', 'label' => 'BC - Behavioral Competency'],
    ['value' => 'MMP', 'label' => 'MMP - Machinery Maintenance & Production'],
    ['value' => 'LC', 'label' => 'LC - Leadership Competency'],
    ['value' => 'MDP', 'label' => 'MDP - Management Development Program'],
    ['value' => 'TOC', 'label' => 'TOC - Technical & Operational Competency'],
  ];

  public array $levelOptions = [
    ['value' => 1, 'label' => 'Level 1 - Reaction (Immediately after training)'],
    ['value' => 3, 'label' => 'Level 3 - Behavior (3 months after training)'],
  ];

  // Current defaults: group_comp => template_id
  public array $defaults = [];

  protected $listeners = [
    'open-default-template-modal' => 'openModal',
  ];

  public function openModal(): void
  {
    $this->loadDefaults();
    $this->modal = true;
  }

  public function loadDefaults(): void
  {
    $this->defaults = [];

    // Load existing defaults for selected level
    $existingDefaults = SurveyTemplateDefault::where('level', $this->level)->get();

    foreach ($existingDefaults as $default) {
      $this->defaults[$default->group_comp] = $default->survey_template_id;
    }
  }

  public function updatedLevel(): void
  {
    $this->loadDefaults();
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

      // Create new defaults
      foreach ($this->defaults as $groupComp => $templateId) {
        if ($templateId) {
          SurveyTemplateDefault::create([
            'survey_template_id' => $templateId,
            'group_comp' => $groupComp,
            'level' => $this->level,
          ]);
        }
      }

      $this->success('Default templates saved successfully.', position: 'toast-top toast-center');
      $this->modal = false;
    } catch (\Throwable $e) {
      $this->error('Failed to save defaults: ' . $e->getMessage(), position: 'toast-top toast-center');
    }
  }

  public function render()
  {
    return view('components.survey-template.default-template-modal', [
      'templateOptions' => $this->templateOptions,
    ]);
  }
}
