<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;

class SurveyManagementDetail extends Component
{
    public $surveyLevel = 1;
    public $surveyId = 1;

    public string $activeTab = 'participants-info';

    public function goNextTab(string $to): void
    {
        $this->activeTab = $to;
    }

    public function back()
    {
        return redirect()->route('survey-management.index', ['level' => $this->surveyLevel]);
    }

    public function mount($level, $surveyId)
    {
        $this->surveyLevel = (int) $level;
        $this->surveyId = (int) $surveyId;
    }

    public function render()
    {
        return view('pages.survey.survey-management-detail', [
        ]);
    }
}
