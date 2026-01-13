<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;
use App\Models\TrainingSurvey;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SurveyInstructorAssessmentExport;
use App\Models\User;

class SurveyManagementDetail extends Component
{
    public $surveyLevel = 1;
    public $surveyId = 1;
    public ?string $surveyName = null;

    public string $activeTab = 'participants-info';

    public function goNextTab(string $to): void
    {
        $this->activeTab = $to;
    }

    public function back()
    {
        return redirect()->route('survey-management.index', ['level' => $this->surveyLevel]);
    }

    /**
     * Export survey answers (only Survey Level 1 & 3) to Excel.
     * Restricted to admin and LID Section Head.
     */
    public function exportAnswers()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
        $isLidSectionHead = $user->hasPosition('section_head') && strtoupper($user->section ?? '') === 'LID';

        if (!$isAdmin && !$isLidSectionHead) {
            $this->dispatch('toast', type: 'error', title: 'Forbidden', message: 'Only admin or LID Section Head can export survey answers.');
            return null;
        }

        if (!in_array((int) $this->surveyLevel, [1, 3], true)) {
            $this->dispatch('toast', type: 'error', title: 'Not Available', message: 'Export is only available for Survey Level 1 and 3.');
            return null;
        }

        $fileName = 'survey_level_' . (int) $this->surveyLevel . '_answers_' . (int) $this->surveyId . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new SurveyInstructorAssessmentExport((int) $this->surveyId),
            $fileName
        );
    }

    public function mount($level, $surveyId)
    {
        $this->surveyLevel = (int) $level;
        $this->surveyId = (int) $surveyId;
        // Fetch survey name from related training for clearer header info
        $survey = TrainingSurvey::with('training')->find($this->surveyId);
        $this->surveyName = $survey?->training?->name;
    }

    public function render()
    {
        return view('pages.survey.survey-management-detail', []);
    }
}
