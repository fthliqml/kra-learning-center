<?php

namespace App\Livewire\Pages\Training;

use App\Models\SurveyResponse;
use App\Models\TrainingSurvey;
use App\Models\TrainingAssessment;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class History extends Component
{
    use Toast, WithPagination;

    public $search = '';
    public $filter = null;

    public $typeOptions = [
        ['value' => 'IN', 'label' => 'In-house'],
        ['value' => 'OUT', 'label' => 'Out-house'],
        ['value' => 'LMS', 'label' => 'LMS'],
    ];

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers()
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center !p-4'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => 'w-[300px]'],
            ['key' => 'type', 'label' => 'Type', 'class' => '!text-center w-[150px]'],
            ['key' => 'group_comp', 'label' => 'Group Comp', 'class' => '!text-center'],
            ['key' => 'instructor', 'label' => 'Instructor', 'class' => '!text-center'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ['key' => 'certificate', 'label' => 'Certificate', 'class' => '!text-center'],
        ];
    }

    public function histories()
    {
        $userId = Auth::id();

        if (!$userId) {
            return TrainingAssessment::query()->whereRaw('1 = 0')->paginate(10);
        }

        $query = TrainingAssessment::query()
            ->with([
                'training.course',
                'training.sessions.trainer.user',
            ])
            ->where('employee_id', $userId)
            ->whereHas('training', function ($q) {
                $q->whereIn('status', ['done', 'approved']);
            })
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->whereHas('training', fn($tq) => $tq->where('name', 'like', '%' . $term . '%'));
            })
            ->when($this->filter, function ($q) {
                $type = $this->filter;
                $q->whereHas('training', fn($tq) => $tq->where('type', $type));
            })
            ->orderByDesc('id');

        $paginator = $query->paginate(10)->onEachSide(1);

        $assessments = $paginator->getCollection();
        $trainingIds = $assessments
            ->pluck('training_id')
            ->filter()
            ->unique()
            ->values();

        $surveyLevel1ByTrainingId = TrainingSurvey::query()
            ->whereIn('training_id', $trainingIds)
            ->where('level', 1)
            ->get(['id', 'training_id'])
            ->keyBy('training_id');

        $surveyIds = $surveyLevel1ByTrainingId->pluck('id')->values();

        $completedSurveyIds = SurveyResponse::query()
            ->whereIn('survey_id', $surveyIds)
            ->where('employee_id', $userId)
            ->where('is_completed', true)
            ->pluck('survey_id')
            ->all();

        $completedSurveyIdSet = array_fill_keys($completedSurveyIds, true);

        return $paginator->through(function ($assessment, $index) use ($paginator, $surveyLevel1ByTrainingId, $completedSurveyIdSet) {
            $training = $assessment->training;

            // Ambil instructor (trainer) dari session pertama training (jika ada)
            $firstSession = $training?->sessions?->first();
            $instructor = $firstSession && $firstSession->trainer
                ? ($firstSession->trainer->name ?? $firstSession->trainer->user->name ?? '-')
                : '-';

            $typeLabel = match ($training?->type) {
                'IN' => 'In-House',
                'OUT' => 'Out-House',
                'LMS' => 'LMS',
                default => (string) ($training?->type ?? '-')
            };

            $start = $paginator->firstItem() ?? 0;

            $surveyLevel1Id = $training?->id ? ($surveyLevel1ByTrainingId[$training->id]->id ?? null) : null;
            $isSurveyLevel1Completed = $surveyLevel1Id ? isset($completedSurveyIdSet[$surveyLevel1Id]) : false;

            return (object) [
                'no' => $start + $index,
                'id' => $training?->id,
                'training_name' => $training?->name ?? '-',
                'type' => $typeLabel,
                'group_comp' => $training?->group_comp,
                'instructor' => $instructor,
                'status' => $assessment->status,
                'assessment_id' => $assessment->id,
                'certificate_path' => $assessment->certificate_path,
                'survey_level1_id' => $surveyLevel1Id,
                'survey_level1_completed' => $isSurveyLevel1Completed,
            ];
        });
    }

    public function render()
    {
        return view('pages.training.training-history', [
            'histories' => $this->histories(),
            'headers' => $this->headers()
        ]);
    }
}
