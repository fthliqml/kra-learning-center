<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\TrainingSurvey;
use App\Models\User;
use Carbon\Carbon;

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
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return TrainingSurvey::query()->whereRaw('1 = 0')->paginate(9);
        }

        // Special rule: Survey Level 3 can only be filled by area approver.
        // - If there is any SPV (position=supervisor) in the area -> SPV area can fill.
        // - If there is no SPV in the area -> Section Head area can fill.
        // Area is based on the user's section (preferred) or department (fallback).
        if ((int) $this->surveyLevel === 3) {
            $areaKey = null;
            $areaValue = null;
            if (!empty($user->section)) {
                $areaKey = 'section';
                $areaValue = $user->section;
            } elseif (!empty($user->department)) {
                $areaKey = 'department';
                $areaValue = $user->department;
            }

            if (!$areaKey || !$areaValue) {
                return TrainingSurvey::query()->whereRaw('1 = 0')->paginate(9);
            }

            $hasSpvInArea = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
                ->whereRaw('LOWER(TRIM(COALESCE(' . $areaKey . ', ""))) = ?', [strtolower(trim((string) $areaValue))])
                ->exists();

            $isSpvAreaUser = $user->hasPosition('supervisor');
            $isSectionHeadAreaUser = $user->hasPosition('section_head');

            if ($hasSpvInArea) {
                if (!$isSpvAreaUser) {
                    return TrainingSurvey::query()->whereRaw('1 = 0')->paginate(9);
                }
            } else {
                if (!$isSectionHeadAreaUser) {
                    return TrainingSurvey::query()->whereRaw('1 = 0')->paginate(9);
                }
            }
        }

        // Base query differs for Level 3 (area approver) vs others (employee participant)
        $base = TrainingSurvey::query()
            ->with([
                'training',
                'training.assessments.employee',
                // My response only (for badge/status)
                'surveyResponses' => function ($q) use ($user) {
                    $q->where('employee_id', $user->id);
                },
            ])
            ->withCount('surveyResponses')
            ->when($this->filterStatus, function ($q) use ($user) {
                // When filtering, only include surveys that have a response for this user
                $q->whereHas('surveyResponses', function ($rq) use ($user) {
                    $rq->where('employee_id', $user->id)
                        ->when($this->filterStatus, function ($rq) {
                            if ($this->filterStatus === 'complete') {
                                $rq->where('is_completed', 1);
                            } elseif ($this->filterStatus === 'incomplete') {
                                $rq->where('is_completed', 0);
                            }
                        });
                });
            })
            ->when($this->surveyLevel, fn($q) => $q->where('level', (int) $this->surveyLevel))
            ->when($this->search, fn($q) => $q->whereHas(
                'training',
                fn($tq) =>
                $tq->where('name', 'like', "%{$this->search}%")
            ))
            // Urutkan berdasarkan status response user (incomplete, complete)
            ->orderByRaw('(
                SELECT CASE
                    WHEN sr.is_completed = 0 THEN 0
                    WHEN sr.is_completed = 1 THEN 1
                    ELSE 2
                END
                FROM survey_responses sr
                WHERE sr.survey_id = training_surveys.id AND sr.employee_id = ?
                LIMIT 1
            ) ASC', [$user->id])
            ->orderByRaw("CASE WHEN status = 'draft' THEN 1 ELSE 0 END ASC")
            ->orderByDesc('id');

        if ((int) $this->surveyLevel === 3) {
            // Show surveys only for trainings that include employees from the user's area.
            if (!empty($user->section)) {
                $base->whereHas('training.assessments.employee', function ($q) use ($user) {
                    $q->where('section', $user->section);
                });
            } elseif (!empty($user->department)) {
                $base->whereHas('training.assessments.employee', function ($q) use ($user) {
                    $q->where('department', $user->department);
                });
            } else {
                $base->whereRaw('1 = 0');
            }
        } else {
            // Default: employee participant surveys
            $base->forEmployee($user->id);
        }

        $paginator = $base->paginate(9)->onEachSide(1);

        return $paginator->through(function ($survey, $index) use ($paginator, $user) {
            $start = $paginator->firstItem() ?? 0;
            $survey->no = $start + $index;
            $survey->training_name = $survey->training?->name ?? '-';
            $survey->participants = (int) ($survey->survey_responses_count ?? 0);
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

            // Compute badge label and class in controller (component) instead of Blade
            $status = $survey->badge_status; // complete | incomplete | null
            $isDraft = ($survey->status ?? '') === 'draft';
            $trainingStatus = strtolower($survey->training?->status ?? '');
            $trainingReady = in_array($trainingStatus, ['done', 'approved'], true);

            // Level 3 additional time gate: only available 3 months after training last day.
            $isLevel3 = ((int) ($survey->level ?? 0)) === 3;
            $availableAt = null;
            $timeReady = true;
            if ($isLevel3) {
                $endDate = $survey->training?->end_date;
                if ($endDate) {
                    $availableAt = Carbon::parse($endDate)->addMonthsNoOverflow(3);
                    $timeReady = now()->greaterThanOrEqualTo($availableAt);
                } else {
                    $timeReady = false;
                }

                // For display on Level 3 cards: who is being assessed (training participants in approver's area)
                $assessments = $survey->training?->assessments ?? collect();
                $employees = collect($assessments)
                    ->map(fn($a) => $a->employee ?? null)
                    ->filter();

                if (!empty($user->section)) {
                    $employees = $employees->filter(fn($e) => (string) ($e->section ?? '') === (string) $user->section);
                } elseif (!empty($user->department)) {
                    $employees = $employees->filter(fn($e) => (string) ($e->department ?? '') === (string) $user->department);
                }

                $names = $employees
                    ->map(fn($e) => trim((string) ($e->name ?? '')))
                    ->filter(fn($n) => $n !== '')
                    ->values();

                if ($names->isEmpty()) {
                    $survey->target_employees_label = '-';
                } else {
                    $shown = $names->take(3);
                    $remaining = $names->count() - $shown->count();
                    $survey->target_employees_label = $shown->implode(', ') . ($remaining > 0 ? ' +' . $remaining . ' lainnya' : '');
                }

                // For display: when the survey can be started
                $survey->available_at = $availableAt?->toDateString();
                $survey->available_at_label = $availableAt ? $availableAt->format('d M Y') : null;
            }

            if ($status === 'complete') {
                $survey->badge_label = 'Complete';
                $survey->badge_class = 'badge-primary bg-primary/95';
            } elseif ($isDraft || !$trainingReady) {
                $survey->badge_label = 'Not Ready';
                $survey->badge_class = 'badge-warning';
            } elseif ($isLevel3 && !$timeReady) {
                $survey->badge_label = 'Not Ready';
                $survey->badge_class = 'badge-warning';
            } elseif ($status === 'incomplete') {
                $survey->badge_label = 'Incomplete';
                $survey->badge_class = 'badge primary badge-soft';
            } else {
                $survey->badge_label = 'Not Started';
                $survey->badge_class = 'badge-ghost';
            }
            // Determine if Start Survey button should be disabled
            $survey->start_disabled = $isDraft || !$trainingReady || ($isLevel3 && !$timeReady);

            // Convenience label for UI
            if ($isLevel3) {
                if ($isDraft || !$trainingReady) {
                    $survey->can_start_label = 'Setelah training selesai';
                } elseif (!$timeReady && $availableAt) {
                    $survey->can_start_label = $availableAt->format('d M Y');
                } else {
                    $survey->can_start_label = 'Sekarang';
                }
            }

            return $survey;
        });
    }

    public function render()
    {
        return view('pages.survey.survey-employee', [
            'surveys' => $this->surveys(),
        ]);
    }
}
