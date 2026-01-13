<?php

namespace App\Livewire\Pages\Survey;

use App\Exports\SurveyInstructorAssessmentExport;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\TrainingSurvey;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class SurveyManagement extends Component
{
    use WithPagination;

    public $surveyLevel = 1;

    public string $search = '';
    public string $filter = '';

    // Follow Training Request convention: value is the actual status key (or 'All')
    public array $groupOptions = [
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'incomplete', 'label' => 'In Progress'],
        ['value' => 'completed', 'label' => 'Completed'],
    ];

    public function mount($level)
    {
        $this->surveyLevel = (int) $level;
    }

    public function updated($property): void
    {
        // Mirror Training Request: reset pagination only for filter/search changes
        if (in_array($property, ['search', 'filter'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Export answers for a specific survey (only level 1 & 3).
     * Restricted to admin and LID Section Head.
     */
    public function exportSurvey(int $surveyId)
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

        $fileName = 'survey_level_' . (int) $this->surveyLevel . '_answers_' . (int) $surveyId . '_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new SurveyInstructorAssessmentExport((int) $surveyId), $fileName);
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
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return TrainingSurvey::query()->whereRaw('1 = 0')->paginate(10);
        }

        $base = TrainingSurvey::query()
            ->where('level', (int) $this->surveyLevel)
            ->with(['training', 'training.assessments'])
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->whereHas('training', fn($tq) => $tq->where('name', 'like', $term));
            })
            ->when($this->filter && strtolower(trim((string) $this->filter)) !== 'all', function ($q) {
                $q->where('status', strtolower(trim((string) $this->filter)));
            });

        // Admin/Leader see all, others filtered
        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
        $isInstructor = method_exists($user, 'hasRole') && $user->hasRole('instructor');
        $isLeader = method_exists($user, 'hasAnyPosition')
            && $user->hasAnyPosition(['section_head', 'department_head', 'division_head', 'director']);

        if (!$isAdmin && !$isLeader) {
            if ($isInstructor) {
                $base = TrainingSurvey::forInstructorUserId($user->id)
                    ->where('level', (int) $this->surveyLevel)
                    ->with(['training', 'training.assessments'])
                    ->when(trim($this->search) !== '', function ($q) {
                        $term = '%' . trim($this->search) . '%';
                        $q->whereHas('training', fn($tq) => $tq->where('name', 'like', $term));
                    })
                    ->when($this->filter && strtolower(trim((string) $this->filter)) !== 'all', function ($q) {
                        $q->where('status', strtolower(trim((string) $this->filter)));
                    });
            } else {
                $base = TrainingSurvey::forEmployeeId($user->id)
                    ->where('level', (int) $this->surveyLevel)
                    ->with(['training', 'training.assessments'])
                    ->when(trim($this->search) !== '', function ($q) {
                        $term = '%' . trim($this->search) . '%';
                        $q->whereHas('training', fn($tq) => $tq->where('name', 'like', $term));
                    })
                    ->when($this->filter && strtolower(trim((string) $this->filter)) !== 'all', function ($q) {
                        $q->where('status', strtolower(trim((string) $this->filter)));
                    });
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
