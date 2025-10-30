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

    public function mount($level)
    {
        $this->surveyLevel = (int) $level;
    }

    public function surveys()
    {
        $user = Auth::user();
        if (!$user) {
            return collect(); // atau paginator kosong
        }

        // Ambil survey yang punya response untuk user ini
        $base = TrainingSurvey::with([
            'training',
            'surveyResponses' => function ($q) use ($user) {
                $q->where('employee_id', $user->id);
            }
        ])
            ->whereHas('surveyResponses', function ($q) use ($user) {
                $q->where('employee_id', $user->id)
                    ->when($this->filterStatus, function ($q) {
                        if ($this->filterStatus === 'complete') {
                            $q->where('is_completed', 1);
                        } elseif ($this->filterStatus === 'incomplete') {
                            $q->where('is_completed', 0);
                        }
                    });
            })
            ->when($this->surveyLevel, fn($q) => $q->where('level', (int) $this->surveyLevel))
            ->when($this->search, fn($q) => $q->whereHas(
                'training',
                fn($tq) =>
                $tq->where('name', 'like', "%{$this->search}%")
            ))
            // tampilkan semua status, termasuk draft
            ->orderByRaw(
                "CASE WHEN status = 'incomplete' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END ASC"
            )
            ->orderByDesc('id');

        $paginator = $base->paginate(9)->onEachSide(1);

        return $paginator->through(function ($survey, $index) use ($paginator, $user) {
            $start = $paginator->firstItem() ?? 0;
            $survey->no = $start + $index;
            $survey->training_name = $survey->training?->name ?? '-';
            $survey->participants = $survey->surveyResponses->count();
            $startDate = $survey->training?->start_date;
            $endDate = $survey->training?->end_date;
            $survey->date = ($startDate && $endDate)
                ? formatRangeDate($startDate, $endDate)
                : '-';
            $survey->my_response = $survey->surveyResponses->first();
            $survey->badge_status = null;
            if ($survey->my_response) {
                $survey->badge_status = $survey->my_response->is_completed ? 'complete' : 'incomplete';
            }
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
