<?php

namespace App\Livewire\Pages\dashboard;

use App\Models\Trainer;
use App\Models\TrainingSurvey;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InstructorPendingSurvey1 extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function getTrainerId(): ?int
    {
        return Trainer::where('user_id', (int) Auth::id())->value('id');
    }

    private function basePendingQuery(int $trainerId)
    {
        return DB::table('training_surveys as ts')
            ->join('trainings as t', 't.id', '=', 'ts.training_id')
            ->join('training_assessments as a', 'a.training_id', '=', 't.id')
            ->join('users as u', 'u.id', '=', 'a.employee_id')
            ->leftJoin('survey_responses as sr', function ($join) {
                $join->on('sr.survey_id', '=', 'ts.id')
                    ->on('sr.employee_id', '=', 'a.employee_id');
            })
            ->where('ts.level', 1)
            ->where('ts.status', '!=', TrainingSurvey::STATUS_DRAFT)
            ->whereIn('t.status', ['done', 'approved'])
            ->whereExists(function ($q) use ($trainerId) {
                $q->select(DB::raw(1))
                    ->from('training_sessions as s')
                    ->whereColumn('s.training_id', 't.id')
                    ->where('s.trainer_id', $trainerId);
            })
            ->where(function ($q) {
                $q->whereNull('sr.id')
                    ->orWhere('sr.is_completed', 0);
            });
    }

    public function render()
    {
        $trainerId = $this->getTrainerId();

        $rows = new LengthAwarePaginator([], 0, 15);
        $totalPending = 0;

        if ($trainerId) {
            $base = $this->basePendingQuery($trainerId);

            if (trim($this->search) !== '') {
                $search = '%' . trim($this->search) . '%';
                $base->where(function ($q) use ($search) {
                    $q->where('u.name', 'like', $search)
                        ->orWhere('u.nrp', 'like', $search)
                        ->orWhere('t.name', 'like', $search);
                });
            }

            $totalPending = (clone $base)->count(DB::raw('DISTINCT CONCAT(ts.id, ":", a.employee_id)'));

            $rows = (clone $base)
                ->select([
                    't.id as training_id',
                    't.name as training_name',
                    't.start_date',
                    't.end_date',
                    'ts.id as survey_id',
                    'ts.status as survey_status',
                    'u.id as employee_id',
                    'u.name as employee_name',
                    'u.nrp as employee_nrp',
                    DB::raw('CASE WHEN sr.id IS NULL THEN "not_filled" WHEN sr.is_completed = 1 THEN "filled" ELSE "in_progress" END as response_status'),
                    'sr.submitted_at',
                ])
                ->distinct()
                ->orderByDesc('t.end_date')
                ->orderBy('t.name')
                ->orderBy('u.name')
                ->paginate(15);
        }

        $headers = [
            ['key' => 'training_name', 'label' => 'Training'],
            ['key' => 'employee_name', 'label' => 'Employee'],
            ['key' => 'employee_nrp', 'label' => 'NRP'],
            ['key' => 'response_status', 'label' => 'Status'],
        ];

        return view('pages.dashboard.instructor-pending-survey-1', [
            'headers' => $headers,
            'rows' => $rows,
            'totalPending' => $totalPending,
        ]);
    }
}
