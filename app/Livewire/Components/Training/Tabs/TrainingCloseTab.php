<?php

namespace App\Livewire\Components\Training\Tabs;

use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Services\TrainingSurveyService;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

class TrainingCloseTab extends Component
{
    use Toast, WithPagination;

    public $trainingId;
    public $training;
    public $search = '';
    public $tempScores = []; // Temporary scores before saving

    protected $listeners = [
        'training-close-save-draft' => 'saveDraft',
        'training-close-close' => 'closeTraining',
    ];

    // Add validation rules for tempScores to validate on update
    protected function rules()
    {
        $isLms = strtoupper((string) ($this->training?->type ?? '')) === 'LMS';

        $rules = [];
        foreach ($this->tempScores as $id => $scores) {
            $rules["tempScores.{$id}.pretest_score"] = 'nullable|numeric|min:0|max:100';
            $rules["tempScores.{$id}.posttest_score"] = 'nullable|numeric|min:0|max:100';
            if (!$isLms) {
                $rules["tempScores.{$id}.practical_score"] = 'nullable|numeric|min:0|max:100';
            }
        }
        return $rules;
    }

    public function mount($trainingId)
    {
        $this->trainingId = $trainingId;
        $this->training = Training::with(['assessments.employee', 'sessions', 'module', 'course'])->find($trainingId);

        // Load existing scores into temp array
        $this->loadTempScores();

        // LMS: override theory score from course posttest and remove practical
        $this->syncLmsScoresFromPosttest();
    }

    public function loadTempScores()
    {
        $assessments = TrainingAssessment::where('training_id', $this->trainingId)->get();
        foreach ($assessments as $assessment) {
            $this->tempScores[$assessment->id] = [
                'pretest_score' => $assessment->pretest_score,
                'posttest_score' => $assessment->posttest_score,
                'practical_score' => $assessment->practical_score,
            ];
        }
    }

    protected function isLms(): bool
    {
        return strtoupper((string) ($this->training?->type ?? '')) === 'LMS';
    }

    /**
     * For LMS trainings, theory score is derived from the Course posttest result (percent) and is not editable.
     */
    protected function syncLmsScoresFromPosttest(): void
    {
        if (!$this->isLms()) {
            return;
        }

        $courseId = (int) ($this->training?->course_id ?? 0);
        if ($courseId <= 0) {
            return;
        }

        $posttest = Test::where('course_id', $courseId)->where('type', 'posttest')->select(['id', 'passing_score'])->first();
        if (!$posttest) {
            return;
        }

        $maxPoints = (int) TestQuestion::where('test_id', $posttest->id)
            ->where('question_type', 'multiple')
            ->sum('max_points');

        $assessments = TrainingAssessment::where('training_id', $this->trainingId)->select(['id', 'employee_id'])->get();
        if ($assessments->isEmpty()) {
            return;
        }

        $userIds = $assessments->pluck('employee_id')->filter()->unique()->values()->all();
        if (empty($userIds)) {
            return;
        }

        $attempts = TestAttempt::where('test_id', $posttest->id)
            ->whereIn('user_id', $userIds)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'total_score', 'submitted_at']);

        $latestByUser = $attempts->groupBy('user_id')->map(fn($rows) => $rows->first());

        foreach ($assessments as $assessment) {
            $aid = (int) $assessment->id;
            if (!isset($this->tempScores[$aid])) {
                $this->tempScores[$aid] = ['pretest_score' => null, 'posttest_score' => null, 'practical_score' => null];
            }

            $attempt = $latestByUser->get($assessment->employee_id);
            if (!$attempt) {
                $this->tempScores[$aid]['posttest_score'] = null;
            } else {
                $score = (int) ($attempt->total_score ?? 0);
                $percent = $maxPoints > 0 ? (int) round(($score / max(1, $maxPoints)) * 100) : 0;
                $this->tempScores[$aid]['posttest_score'] = $percent;
            }

            // LMS has no practical score
            $this->tempScores[$aid]['practical_score'] = null;
        }
    }

    public function updated($property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }
    public function headers()
    {
        if ($this->isLms()) {
            return [
                ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
                ['key' => 'employee_name', 'label' => 'Employee Name', 'class' => 'min-w-[150px]'],
                ['key' => 'posttest_score', 'label' => 'Theory Score', 'class' => '!text-center min-w-[120px]'],
                ['key' => 'progress', 'label' => 'Progress', 'class' => '!text-center min-w-[120px]'],
                ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ];
        }

        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'employee_name', 'label' => 'Employee Name', 'class' => 'min-w-[150px]'],
            ['key' => 'attendance_percentage', 'label' => 'Attendance', 'class' => '!text-center min-w-[110px]'],
            ['key' => 'posttest_score', 'label' => 'Post-test Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'practical_score', 'label' => 'Practical Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
        ];
    }

    public function assessments()
    {
        if (!$this->training) {
            return collect();
        }

        // Ensure LMS-derived theory score is always up-to-date when rendering
        $this->syncLmsScoresFromPosttest();

        $query = TrainingAssessment::query()
            ->with('employee')
            ->where('training_id', $this->trainingId)
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function ($assessment, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $assessment->no = $start + $index;
            $assessment->employee_name = $assessment->employee->name ?? '-';

            // Ensure tempScores exists for this assessment
            if (!isset($this->tempScores[$assessment->id])) {
                $this->tempScores[$assessment->id] = [
                    'pretest_score' => $assessment->pretest_score,
                    'posttest_score' => $assessment->posttest_score,
                    'practical_score' => $assessment->practical_score,
                ];
            }

            // Use temp scores
            $pretestScore = $this->tempScores[$assessment->id]['pretest_score'];
            $posttestScore = $this->tempScores[$assessment->id]['posttest_score'];
            $practicalScore = $this->tempScores[$assessment->id]['practical_score'];

            $isLms = $this->isLms();
            $course = $this->training?->course;
            $assessment->is_lms = $isLms;
            $assessment->lms_progress = ($isLms && $course && $assessment->employee)
                ? (int) $course->progressForUser($assessment->employee)
                : null;

            // Attach temp scores to assessment for display
            $assessment->temp_pretest = $pretestScore;
            $assessment->temp_posttest = $posttestScore;
            $assessment->temp_practical = $practicalScore;

            // Calculate attendance percentage
            $totalSessions = $this->training->sessions()->count();
            if ($totalSessions > 0) {
                $sessionIds = $this->training->sessions()->pluck('id')->toArray();
                $presentCount = TrainingAttendance::whereIn('session_id', $sessionIds)
                    ->where('employee_id', $assessment->employee_id)
                    ->where('status', 'present')
                    ->count();
                $assessment->attendance_percentage = round(($presentCount / $totalSessions) * 100, 1);
            } else {
                $assessment->attendance_percentage = 0;
            }

            // Calculate average score (for non-LMS only)
            if (!$isLms) {
                $scores = [];
                if ($posttestScore !== null && $posttestScore !== '') {
                    $scores[] = (float) $posttestScore;
                }
                if ($practicalScore !== null && $practicalScore !== '') {
                    $scores[] = (float) $practicalScore;
                }
                $assessment->average_score = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
            }

            // Calculate temp status
            $hasPosttest = is_numeric($posttestScore) && $posttestScore !== '';
            $attendancePassed = $assessment->attendance_percentage >= 75;

            if ($isLms) {
                if (!$hasPosttest) {
                    $assessment->temp_status = 'pending';
                } else {
                    // Check attendance first
                    if (!$attendancePassed) {
                        $assessment->temp_status = 'failed';
                    } else {
                        $passing = (int) (Test::where('course_id', (int) ($this->training?->course_id ?? 0))
                            ->where('type', 'posttest')
                            ->value('passing_score') ?? 0);
                        $assessment->temp_status = ($passing > 0 && (float) $posttestScore >= $passing) ? 'passed' : (($passing > 0) ? 'failed' : 'passed');
                    }
                }
            } else {
                $hasPractical = is_numeric($practicalScore) && $practicalScore !== '';
                if (!$hasPosttest || !$hasPractical) {
                    $assessment->temp_status = 'pending';
                } else {
                    // Check attendance first
                    if (!$attendancePassed) {
                        $assessment->temp_status = 'failed';
                    } else {
                        $theoryPassingScore = $this->training->module->theory_passing_score ?? 60;
                        $practicalPassingScore = $this->training->module->practical_passing_score ?? 60;
                        $theoryPassed = (float) $posttestScore >= $theoryPassingScore;
                        $practicalPassed = (float) $practicalScore >= $practicalPassingScore;
                        $assessment->temp_status = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
                    }
                }
            }

            // Expose training closed flag directly on assessment for blade scopes (avoid scope isolation issues)
            $assessment->training_done = in_array(strtolower($this->training->status ?? ''), ['done', 'approved', 'rejected']);

            return $assessment;
        });
    }

    public function saveDraft()
    {
        try {
            DB::transaction(function () {
                $isLms = $this->isLms();

                if ($isLms) {
                    $this->syncLmsScoresFromPosttest();
                }

                foreach ($this->tempScores as $assessmentId => $scores) {
                    $assessment = TrainingAssessment::find($assessmentId);
                    if (!$assessment)
                        continue;

                    $assessment->pretest_score = $scores['pretest_score'];
                    $assessment->posttest_score = $scores['posttest_score'];
                    $assessment->practical_score = $isLms ? null : $scores['practical_score'];

                    // Calculate attendance percentage
                    $totalSessions = $this->training->sessions()->count();
                    $attendancePassed = false;
                    if ($totalSessions > 0) {
                        $sessionIds = $this->training->sessions()->pluck('id')->toArray();
                        $presentCount = TrainingAttendance::whereIn('session_id', $sessionIds)
                            ->where('employee_id', $assessment->employee_id)
                            ->where('status', 'present')
                            ->count();
                        $attendancePercentage = ($presentCount / $totalSessions) * 100;
                        $attendancePassed = $attendancePercentage >= 75;
                    }

                    // Calculate status
                    $posttest = $scores['posttest_score'];
                    if ($isLms) {
                        if (is_numeric($posttest)) {
                            // Check attendance first
                            if (!$attendancePassed) {
                                $assessment->status = 'failed';
                            } else {
                                $passing = (int) (Test::where('course_id', (int) ($this->training?->course_id ?? 0))
                                    ->where('type', 'posttest')
                                    ->value('passing_score') ?? 0);
                                $assessment->status = ($passing > 0 && (float) $posttest >= $passing) ? 'passed' : (($passing > 0) ? 'failed' : 'passed');
                            }
                        } else {
                            $assessment->status = 'pending';
                        }
                    } else {
                        $practical = $scores['practical_score'];
                        if (is_numeric($posttest) && is_numeric($practical)) {
                            // Check attendance first
                            if (!$attendancePassed) {
                                $assessment->status = 'failed';
                            } else {
                                $theoryPassingScore = $this->training->module->theory_passing_score ?? 60;
                                $practicalPassingScore = $this->training->module->practical_passing_score ?? 60;
                                $theoryPassed = (float) $posttest >= $theoryPassingScore;
                                $practicalPassed = (float) $practical >= $practicalPassingScore;
                                $assessment->status = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
                            }
                        } else {
                            $assessment->status = 'pending';
                        }
                    }

                    $assessment->save();
                }
            });

            $this->success('Draft saved successfully.', position: 'toast-top toast-center');
        } catch (\Throwable $e) {
            $this->error('Failed to save draft.', position: 'toast-top toast-center');
        }
    }

    public function closeTraining()
    {
        if (!$this->training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }

        // Check if training is already approved or rejected
        $status = strtolower($this->training->status ?? '');
        if (in_array($status, ['approved', 'rejected'])) {
            $this->error('Cannot close training that has already been ' . $status . ' by LID Section Head.', position: 'toast-top toast-center');
            return;
        }

        // Check if training is already done
        if ($status === 'done') {
            $this->error('Training has already been closed.', position: 'toast-top toast-center');
            return;
        }

        $isLms = $this->isLms();

        // For non-LMS trainings, attendance must be complete before closing
        if (!$isLms) {
            $sessionsWithMissingAttendance = [];
            $sessions = $this->training->sessions()->with('attendances')->get();
            $totalEmployees = TrainingAssessment::where('training_id', $this->trainingId)->count();

            foreach ($sessions as $index => $session) {
                $filledAttendances = $session->attendances()
                    ->whereIn('status', ['present', 'absent'])
                    ->count();

                if ($filledAttendances < $totalEmployees) {
                    $sessionsWithMissingAttendance[] = 'Day ' . ($index + 1);
                }
            }

            if (!empty($sessionsWithMissingAttendance)) {
                $this->error('All attendance records must be completed before closing the training. Missing attendance on: ' . implode(', ', $sessionsWithMissingAttendance), position: 'toast-top toast-center');
                return;
            }
        }

        // Ensure LMS-derived scores are present
        if ($isLms) {
            $this->syncLmsScoresFromPosttest();
        }

        // Check if all assessments have required scores filled
        $allHaveScores = true;
        foreach ($this->tempScores as $assessmentId => $scores) {
            $posttest = $scores['posttest_score'];
            if ($posttest === null || $posttest === '') {
                $allHaveScores = false;
                break;
            }

            if (!$isLms) {
                $practical = $scores['practical_score'];
                if ($practical === null || $practical === '') {
                    $allHaveScores = false;
                    break;
                }
            }
        }

        if (!$allHaveScores) {
            $this->error(
                $isLms
                ? 'All employees must have theory (posttest) scores completed before closing the training.'
                : 'All employees must have both posttest and practical scores completed before closing the training.',
                position: 'toast-top toast-center'
            );
            return;
        }

        try {
            DB::transaction(function () use ($isLms) {
                $lmsPassingScore = null;
                if ($isLms) {
                    $lmsPassingScore = (int) (Test::where('course_id', (int) ($this->training?->course_id ?? 0))
                        ->where('type', 'posttest')
                        ->value('passing_score') ?? 0);
                }

                // Save all temp scores to database
                foreach ($this->tempScores as $assessmentId => $scores) {
                    $assessment = TrainingAssessment::find($assessmentId);
                    if (!$assessment)
                        continue;

                    $assessment->pretest_score = $scores['pretest_score'];
                    $assessment->posttest_score = $scores['posttest_score'];
                    $assessment->practical_score = $isLms ? null : $scores['practical_score'];

                    // Calculate attendance percentage
                    $totalSessions = $this->training->sessions()->count();
                    $attendancePassed = false;
                    if ($totalSessions > 0) {
                        $sessionIds = $this->training->sessions()->pluck('id')->toArray();
                        $presentCount = TrainingAttendance::whereIn('session_id', $sessionIds)
                            ->where('employee_id', $assessment->employee_id)
                            ->where('status', 'present')
                            ->count();
                        $attendancePercentage = ($presentCount / $totalSessions) * 100;
                        $attendancePassed = $attendancePercentage >= 75;
                    }

                    // Calculate status
                    $posttest = $assessment->posttest_score;
                    if ($isLms) {
                        if ($posttest === null || $posttest === '') {
                            $assessment->status = 'pending';
                        } else {
                            // Check attendance first
                            if (!$attendancePassed) {
                                $assessment->status = 'failed';
                            } else {
                                $assessment->status = ($lmsPassingScore > 0 && (float) $posttest >= $lmsPassingScore)
                                    ? 'passed'
                                    : (($lmsPassingScore > 0) ? 'failed' : 'passed');
                            }
                        }
                    } else {
                        $practical = $assessment->practical_score;
                        if ($posttest === null || $posttest === '' || $practical === null || $practical === '') {
                            $assessment->status = 'pending';
                        } else {
                            // Check attendance first
                            if (!$attendancePassed) {
                                $assessment->status = 'failed';
                            } else {
                                $theoryPassingScore = $this->training->module->theory_passing_score ?? 60;
                                $practicalPassingScore = $this->training->module->practical_passing_score ?? 60;
                                $theoryPassed = (float) $posttest >= $theoryPassingScore;
                                $practicalPassed = (float) $practical >= $practicalPassingScore;
                                $assessment->status = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
                            }
                        }
                    }

                    $assessment->save();
                }

                // Update training status to done
                $this->training->status = 'done';
                $this->training->save();

                // Auto-create Level 1 survey with default template
                $surveyService = new TrainingSurveyService();
                $surveyService->createSurveyForTraining($this->training);
            });

            $this->success('Training has been closed successfully. Survey Level 1 has been created for participants.', position: 'toast-top toast-center');
            $this->dispatch('training-closed', ['id' => $this->training->id]);
            $this->dispatch('close-modal');
        } catch (\Throwable $e) {
            $this->error('Failed to close training.', position: 'toast-top toast-center');
        }
    }

    public function placeholder()
    {
        return view('components.skeletons.training-close-tab');
    }

    public function render()
    {
        return view('components.training.tabs.training-close-tab', [
            'assessments' => $this->assessments(),
            'headers' => $this->headers(),
            'training' => $this->training,
        ]);
    }
}
