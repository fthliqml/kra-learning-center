<?php

namespace App\Livewire\Components\Survey;

use Livewire\Component;
use Livewire\WithPagination;

class SurveyParticipants extends Component
{
    use WithPagination;
    public $surveyLevel = 1;
    public $surveyId = 1;

    public $headers = [
        ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
        ['key' => 'nrp', 'label' => 'NRP'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'section', 'label' => 'Section'],
        ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
    ];

    public function participants()
    {
        $query = \App\Models\SurveyResponse::with('employee')
            ->where('survey_id', $this->surveyId)
            ->orderBy('id');

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function ($resp, $index) use ($paginator) {
            $user = $resp->employee;
            $start = $paginator->firstItem() ?? 0;
            return [
                'no' => $start + $index,
                'nrp' => $user?->nrp ?? '-',
                'name' => $user?->name ?? '-',
                'section' => $user?->section ?? '-',
                'status' => $resp->is_completed ? 'filled' : 'not filled',
            ];
        });
    }

    public function render()
    {
        $rows = $this->participants();
        return view('components.survey.survey-participants', [
            'rows' => $rows,
            'headers' => $this->headers,
        ]);
    }

    public function placeholder()
    {
        return view('components.skeletons.survey-participants-skeleton');
    }
}
