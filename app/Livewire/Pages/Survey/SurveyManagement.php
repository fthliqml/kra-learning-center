<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\TrainingSurvey;

class SurveyManagement extends Component
{
    public $surveyLevel = 1;

    public function mount($level)
    {
        $this->surveyLevel = (int) $level;
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => 'w-[300px]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-start w-[300px]'],
            ['key' => 'participant', 'label' => 'Participants', 'class' => '!text-center w-[140px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function surveys()
    {
        $user = Auth::user();
        $base = TrainingSurvey::query()->where('level', (int) $this->surveyLevel)
            ->with(['training', 'training.assessments']);

        // Admin/Leader see all, others filtered
        $role = strtolower((string) ($user->role ?? ''));
        $isAdminOrLeader = in_array($role, ['admin', 'leader'], true);
        if ($user && !$isAdminOrLeader) {
            if ($role === 'instructor') {
                $base = TrainingSurvey::forInstructorUserId($user->id)->where('level', (int) $this->surveyLevel)->with(['training', 'training.assessments']);
            } else {
                $base = TrainingSurvey::forEmployeeId($user->id)->where('level', (int) $this->surveyLevel)->with(['training', 'training.assessments']);
            }
        }

        $paginator = $base->orderByDesc('id')->paginate(10)->onEachSide(1);
        return $paginator->through(function ($survey, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $survey->no = $start + $index;
            $survey->training_name = $survey->training?->name ?? '-';
            $survey->participant = $survey->training?->assessments?->count() ?? 0;
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
        return view('pages.survey.survey-management', [
            'headers' => $this->headers(),
            'surveys' => $this->surveys(),
        ]);
    }
}
