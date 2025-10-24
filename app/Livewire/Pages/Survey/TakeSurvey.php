<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;

class TakeSurvey extends Component
{
    public $surveyLevel = 1;
    public $surveyId = 1;

    public function mount($levelId, $surveyId)
    {
        $this->surveyLevel = (int) $levelId;
        $this->surveyId = (int) $surveyId;
    }

    public function render()
    {
        return view('pages.survey.take-survey', [
        ]);
    }
}
