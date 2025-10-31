<?php

namespace App\Livewire\Pages\SurveyTemplate;

use App\Models\SurveyTemplate as ModelsSurveyTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class SurveyTemplate extends Component
{
  use WithPagination;
  public $filterOptions = [
    ['value' => 1, 'label' => 'Level 1'],
    ['value' => 2, 'label' => 'Level 2'],
    ['value' => 3, 'label' => 'Level 3'],
  ];

  public $search = '';
  public $filter = null;

  public function updated($property): void
  {
    if (!is_array($property) && $property != "") {
      $this->resetPage();
    }
  }

  public function surveyTemplates()
  {
    return ModelsSurveyTemplate::query()
      // Filter by search
      ->when(
        $this->search,
        fn($q) =>
        $q->where('title', 'like', '%' . $this->search . '%')
      )
      // Filter by level
      ->when(
        $this->filter,
        fn($q) =>
        $q->where('level', $this->filter)
      )
      ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
      ->orderBy('created_at', 'desc')
      ->paginate(9);
  }

  public function addPage(): void
  {
    // TODO: Implement add/edit page

  }

  public function render()
  {
    return view('pages.survey-template.survey-template', [
      'surveyTemplates' => $this->surveyTemplates(),
    ]);
  }
}
