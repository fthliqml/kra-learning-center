<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\TrainingSurvey;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AdminPendingSurveys extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Allowed: '' (all), '1', '3'
     */
    public string $level = '';

    public array $levelOptions = [
        ['id' => '', 'name' => 'All'],
        ['id' => '1', 'name' => 'Survey 1'],
        ['id' => '3', 'name' => 'Survey 3'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLevel(): void
    {
        $this->resetPage();
    }

    private function basePendingResponsesQuery()
    {
        $level3AvailableThreshold = now()->subMonthsNoOverflow(3)->endOfDay();

        return DB::table('training_surveys as ts')
            ->join('trainings as t', 't.id', '=', 'ts.training_id')
            ->join('training_assessments as a', 'a.training_id', '=', 't.id')
            ->join('users as u', 'u.id', '=', 'a.employee_id')
            ->leftJoin('survey_responses as sr', function ($join) {
                $join->on('sr.survey_id', '=', 'ts.id')
                    ->on('sr.employee_id', '=', 'a.employee_id');
            })
            ->whereIn('ts.level', [1, 3])
            ->where('ts.status', '!=', TrainingSurvey::STATUS_DRAFT)
            ->whereIn('t.status', ['done', 'approved'])
            ->where(function ($q) use ($level3AvailableThreshold) {
                // Level 1: always pending immediately after training
                $q->where('ts.level', 1)
                    // Level 3: only when survey is available (>= end_date + 3 months)
                    ->orWhere(function ($q2) use ($level3AvailableThreshold) {
                        $q2->where('ts.level', 3)
                            ->whereNotNull('t.end_date')
                            ->where('t.end_date', '<=', $level3AvailableThreshold);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('sr.id')
                    ->orWhere('sr.is_completed', 0);
            });
    }

    public function render()
    {
        $base = $this->basePendingResponsesQuery();

        if (trim($this->level) !== '') {
            $base->where('ts.level', (int) $this->level);
        }

        if (trim($this->search) !== '') {
            $search = '%' . trim($this->search) . '%';
            $base->where(function ($q) use ($search) {
                $q->where('t.name', 'like', $search)
                    ->orWhere('u.name', 'like', $search)
                    ->orWhere('u.nrp', 'like', $search)
                    ->orWhere('u.position', 'like', $search)
                    ->orWhere('u.section', 'like', $search)
                    ->orWhere('u.department', 'like', $search);
            });
        }

        $totalPending = (int) (clone $base)->count(DB::raw('DISTINCT CONCAT(ts.id, ":", a.employee_id)'));

        $rows = (clone $base)
            ->select([
                'ts.id as survey_id',
                'ts.level as survey_level',
                'ts.status as survey_status',
                't.id as training_id',
                't.name as training_name',
                't.start_date',
                't.end_date',
                'u.id as user_id',
                'u.name as user_name',
                'u.nrp as user_nrp',
                'u.position as user_position',
                'u.section as user_section',
                'u.department as user_department',
                'sr.id as response_id',
                'sr.submitted_at',
                DB::raw('CASE WHEN sr.id IS NULL THEN "not_filled" WHEN sr.is_completed = 1 THEN "filled" ELSE "in_progress" END as response_status'),
            ])
            ->distinct()
            ->orderByDesc('t.end_date')
            ->orderBy('t.name')
            ->orderBy('ts.level')
            ->orderBy('u.name')
            ->paginate(15);

        $headers = [
            ['key' => 'training_name', 'label' => 'Training'],
            ['key' => 'survey_level', 'label' => 'Survey'],
            ['key' => 'user_name', 'label' => 'Responder'],
            ['key' => 'user_nrp', 'label' => 'NRP'],
            ['key' => 'user_position', 'label' => 'Position'],
            ['key' => 'user_section', 'label' => 'Section'],
            ['key' => 'response_status', 'label' => 'Status'],
        ];

        return view('pages.dashboard.pending-surveys', [
            'showLevelFilter' => true,
            'levelOptions' => $this->levelOptions,
            'headers' => $headers,
            'rows' => $rows,
            'totalPending' => $totalPending,
        ]);
    }
}
