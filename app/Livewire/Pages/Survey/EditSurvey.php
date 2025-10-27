<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;

class EditSurvey extends Component
{
    public $surveyLevel = 1;
    public $surveyId = 1;

    public function mount($level, $surveyId)
    {
        $this->surveyLevel = (int) $level;
        $this->surveyId = (int) $surveyId;
    }

    public function render()
    {
        return view('pages.survey.edit-survey', [
        ]);
    }
}
