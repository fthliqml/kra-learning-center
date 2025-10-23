<?php

namespace App\Livewire\Pages\SurveyTemplate;

use App\Models\SurveyTemplate as ModelsSurveyTemplate;
use Livewire\Component;

class SurveyTemplate extends Component
{
    public function mount()
    {
    }

    public function surveyTemplates()
    {
        return ModelsSurveyTemplate::all();
    }

    public function openEditModal($id): void
    {
        // TODO: Implement edit/view survey template

    }

    public function render()
    {
        return view('pages.survey-template.survey-template', [
            'surveyTemplates' => $this->surveyTemplates(),
        ]);
    }
}
