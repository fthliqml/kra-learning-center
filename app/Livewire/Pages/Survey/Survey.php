<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;

class Survey extends Component
{
    public $surveyLevel = 1;

    public function mount($id)
    {
        $this->surveyLevel = $id;
    }

    public function render()
    {
        return view('pages.survey.survey');
    }
}
