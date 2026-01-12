<?php

namespace App\Livewire\Components\Survey;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TrainingSurvey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
        if ((int) $this->surveyLevel === 3) {
            return $this->level3ApproverRows();
        }

        $query = SurveyResponse::with('employee')
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

    private function level3ApproverRows(): LengthAwarePaginator
    {
        $survey = TrainingSurvey::with('training.assessments')->find($this->surveyId);
        if (!$survey || !$survey->training) {
            return new LengthAwarePaginator([], 0, 10, 1);
        }

        $participantIds = $survey->training->assessments()->pluck('employee_id')->map(fn($id) => (int) $id)->toArray();
        if (empty($participantIds)) {
            return new LengthAwarePaginator([], 0, 10, 1);
        }

        $participants = User::query()
            ->whereIn('id', $participantIds)
            ->get(['id', 'nrp', 'name', 'section', 'department', 'position']);

        $approverIds = collect();

        foreach ($participants as $participant) {
            foreach ($this->resolveApproversForParticipant($participant) as $approverId) {
                $approverIds->push($approverId);
            }
        }

        $approverIds = $approverIds->filter()->unique()->values();
        if ($approverIds->isEmpty()) {
            return new LengthAwarePaginator([], 0, 10, 1);
        }

        $approvers = User::query()
            ->whereIn('id', $approverIds->all())
            ->get(['id', 'nrp', 'name', 'section', 'department', 'position'])
            ->keyBy('id');

        $responses = SurveyResponse::query()
            ->where('survey_id', $this->surveyId)
            ->whereIn('employee_id', $approverIds->all())
            ->get(['employee_id', 'is_completed'])
            ->keyBy('employee_id');

        $rows = $approverIds
            ->map(function (int $id) use ($approvers, $responses) {
                $user = $approvers->get($id);
                $resp = $responses->get($id);
                $sectionOrDept = $user?->section ?: ($user?->department ?: '-');

                return [
                    'nrp' => $user?->nrp ?? '-',
                    'name' => $user?->name ?? '-',
                    'section' => $sectionOrDept,
                    'status' => ($resp && $resp->is_completed) ? 'filled' : 'not filled',
                ];
            })
            ->values();

        $perPage = 10;
        $page = (int) request()->query('page', 1);
        $page = max(1, $page);
        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginator->through(function ($row, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            return array_merge(['no' => $start + $index], $row);
        });
    }

    /**
     * Resolve the expected level-3 approver(s) for a participant.
     * Priority by participant area: SPV -> Section Head -> Dept Head.
     * If participant is already a supervisor/section head, route to the next level.
     */
    private function resolveApproversForParticipant(User $participant): array
    {
        $position = strtolower(trim($participant->position ?? ''));
        $section = (string) ($participant->section ?? '');
        $department = (string) ($participant->department ?? '');

        // Participant is supervisor -> Section Head -> Dept Head
        if ($position === 'supervisor') {
            $sectionHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
                ->when($section !== '', fn($q) => $q->where('section', $section))
                ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
                ->first();
            if ($sectionHead) {
                return [(int) $sectionHead->id];
            }

            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();
            return $deptHead ? [(int) $deptHead->id] : [];
        }

        // Participant is section head -> Dept Head
        if ($position === 'section_head') {
            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();
            return $deptHead ? [(int) $deptHead->id] : [];
        }

        // Default participant: SPV -> Section Head -> Dept Head (by area)
        $spv = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
            ->when($section !== '', fn($q) => $q->where('section', $section))
            ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
            ->first();
        if ($spv) {
            return [(int) $spv->id];
        }

        $sectionHead = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
            ->when($section !== '', fn($q) => $q->where('section', $section))
            ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
            ->first();
        if ($sectionHead) {
            return [(int) $sectionHead->id];
        }

        $deptHead = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
            ->when($department !== '', fn($q) => $q->where('department', $department))
            ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
            ->first();

        return $deptHead ? [(int) $deptHead->id] : [];
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
