<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\TrainingSurvey;

class SurveyEmployee extends Component
{
    public $surveyLevel = 1;
    public $filterOptions = [
        ['value' => 'complete', 'label' => 'Complete'],
        ['value' => 'incomplete', 'label' => 'Incomplete'],
    ];

    public $search = '';
    public $filterStatus = '';

    public function mount($levelId)
    {
        $this->surveyLevel = (int) $levelId;
    }

    public function surveys()
    {
        $user = Auth::user();
        if (!$user) {
            return collect(); // atau paginate kosong
        }

        $base = TrainingSurvey::forEmployeeId($user->id)
            ->with(['training', 'training.assessments'])
            ->when($this->surveyLevel, fn($q) => $q->where('level', (int) $this->surveyLevel))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn($q) => $q->whereHas(
                'training',
                fn($tq) =>
                $tq->where('name', 'like', "%{$this->search}%")
            ))
            ->whereIn('status', [
                TrainingSurvey::STATUS_INCOMPLETE,
                TrainingSurvey::STATUS_COMPLETED,
            ])
            ->orderByRaw(
                "CASE WHEN status = 'incomplete' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END ASC"
            )
            ->orderByDesc('id');

        $paginator = $base->paginate(9)->onEachSide(1);

        return $paginator->through(function ($survey, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $survey->no = $start + $index;
            $survey->training_name = $survey->training?->name ?? '-';
            $survey->participants = $survey->training?->assessments?->count() ?? 0;
            $startDate = $survey->training?->start_date;
            $endDate = $survey->training?->end_date;
            $survey->date = ($startDate && $endDate)
                ? formatRangeDate($startDate, $endDate)
                : '-';
            return $survey;
        });
    }


    public function openEditModal($id): void
    {
        // TODO: Implement edit/view survey flow

    }

    public function render()
    {
        return view('pages.survey.survey-employee', [
            'surveys' => $this->surveys(),
        ]);
    }
}
