<?php

namespace App\Livewire\Components\Survey;

use Livewire\Component;


class SurveyParticipants extends Component
{
    public $surveyLevel = 1;
    public $surveyId = 1;

    public $participants = [];

    public function mount()
    {

    }

    public function render()
    {
        return view('components.survey.survey-participants', [
        ]);
    }
}
