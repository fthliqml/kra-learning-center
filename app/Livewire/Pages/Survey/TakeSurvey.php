<?php

namespace App\Livewire\Pages\Survey;

use App\Models\SurveyQuestion;
use Livewire\Component;

class TakeSurvey extends Component
{
    public $surveyLevel = 1;
    public $surveyId = 1;
    public $questions = '';

    public function mount($levelId, $surveyId)
    {
        $this->surveyLevel = (int) $levelId;
        $this->surveyId = (int) $surveyId;
        $this->questions = SurveyQuestion::with('options')->orderBy('order')->get();
    }

    public function render()
    {
        return view('pages.survey.take-survey', [
        ]);
    }
}
