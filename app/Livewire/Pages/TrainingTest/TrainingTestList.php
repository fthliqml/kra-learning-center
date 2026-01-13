<?php

namespace App\Livewire\Pages\TrainingTest;

use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TrainingTestList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Get test status for a training.
     * Supports both IN (module-based) and LMS (course-based) training types.
     */
    public function getTestStatus(Training $training, int $userId): array
    {
        $pretest = null;
        $posttest = null;

        // Determine test source based on training type
        if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
            // LMS and BLENDED: Tests are linked to Course
            $pretest = $training->course->tests->firstWhere('type', 'pretest');
            $posttest = $training->course->tests->firstWhere('type', 'posttest');
        } elseif ($training->type === 'IN' && $training->module) {
            // IN: Tests are linked to TrainingModule
            $pretest = $training->module->pretest;
            $posttest = $training->module->posttest;
        }

        // Default values
        $pretestStatus = 'unavailable';
        $posttestStatus = 'unavailable';
        $pretestScore = null;
        $posttestScore = null;
        $posttestAttempts = 0;
        $posttestMaxAttempts = null;
        $posttestPassed = false;

        // PRETEST: Only check for 1 attempt
        if ($pretest) {
            $attempt = TestAttempt::where('test_id', $pretest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
                ->latest()
                ->first();

            if ($attempt) {
                if ($attempt->status === TestAttempt::STATUS_UNDER_REVIEW) {
                    $pretestStatus = 'under_review';
                    $pretestScore = null;
                } else {
                    $pretestStatus = 'completed';
                    $pretestScore = $attempt->total_score;
                }
            } else {
                $pretestStatus = 'available';
            }
        }

        // POSTTEST: Check multiple attempts, pass status, and max attempts
        if ($posttest) {
            $posttestMaxAttempts = $posttest->max_attempts; // null = unlimited

            $attempts = TestAttempt::where('test_id', $posttest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
                ->orderBy('attempt_number', 'desc')
                ->get();

            $posttestAttempts = $attempts->count();

            if ($attempts->isNotEmpty()) {
                $latestAttempt = $attempts->first();
                $hasUnderReview = $attempts->contains('status', TestAttempt::STATUS_UNDER_REVIEW);
                $hasPassed = $attempts->contains('is_passed', true);
                $posttestPassed = $hasPassed;

                // Get best score from completed attempts
                $completedAttempts = $attempts->where('status', TestAttempt::STATUS_SUBMITTED);
                $posttestScore = $completedAttempts->max('total_score');

                if ($hasUnderReview) {
                    $posttestStatus = 'under_review';
                    $posttestScore = null; // Don't show score until reviewed
                } elseif ($hasPassed) {
                    $posttestStatus = 'completed';
                } else {
                    // Failed - check if can retake
                    $canRetake = !$posttestMaxAttempts || $posttestMaxAttempts == 0 || $posttestAttempts < $posttestMaxAttempts;
                    $posttestStatus = $canRetake ? 'retake' : 'failed';
                }
            } else {
                // No attempts yet - check prerequisites
                if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
                    // LMS: Must complete pretest + all learning modules
                    $learningComplete = $training->course->hasCompletedLearningForUser($userId);
                    $posttestStatus = $learningComplete ? 'available' : 'locked';
                } else {
                    // IN: Only need pretest completed
                    $posttestStatus = in_array($pretestStatus, ['completed', 'under_review']) ? 'available' : 'locked';
                }
            }
        }

        return [
            'pretest' => $pretestStatus,
            'posttest' => $posttestStatus,
            'pretestScore' => $pretestScore,
            'posttestScore' => $posttestScore,
            'posttestAttempts' => $posttestAttempts,
            'posttestMaxAttempts' => $posttestMaxAttempts,
            'posttestPassed' => $posttestPassed,
        ];
    }

    public function render()
    {
        $userId = Auth::id();

        // Get IN, LMS, and BLENDED trainings where user is assigned
        $trainings = Training::with([
            'module.pretest',
            'module.posttest',
            'course.tests',
            'competency'
        ])
            ->whereIn('type', ['IN', 'LMS', 'BLENDED'])
            ->whereIn('status', ['in_progress'])
            // Filter by start date: IN/BLENDED shows H-1 (day before), LMS shows on hari H (start_date)
            ->where(function ($query) {
                $today = now()->toDateString();
                $tomorrow = now()->addDay()->toDateString();
                
                // IN and BLENDED type: show from H-1 (start_date <= tomorrow)
                $query->where(function ($q) use ($tomorrow) {
                    $q->whereIn('type', ['IN', 'BLENDED'])
                      ->where('start_date', '<=', $tomorrow);
                })
                // LMS type: show from hari H (start_date <= today)
                ->orWhere(function ($q) use ($today) {
                    $q->where('type', 'LMS')
                      ->where('start_date', '<=', $today);
                });
            })
            ->whereHas('assessments', fn($q) => $q->where('employee_id', $userId))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('module', fn($m) => $m->where('title', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('course', fn($c) => $c->where('title', 'like', '%' . $this->search . '%'));
                });
            })
            ->get();

        // Add test status to each training
        $trainings->transform(function ($training) use ($userId) {
            $training->testStatus = $this->getTestStatus($training, $userId);
            return $training;
        });

        // Smart sorting: active trainings first (with pending tests), completed trainings at bottom
        // Both groups sorted by start_date (newest first)
        $sorted = $trainings->sort(function ($a, $b) {
            $aStatus = $a->testStatus;
            $bStatus = $b->testStatus;
            
            // Determine if training is fully completed
            // Completed = pretest done AND posttest passed (completed status, not retake/failed/locked)
            $aCompleted = in_array($aStatus['pretest'], ['completed', 'under_review']) 
                       && $aStatus['posttest'] === 'completed';
            $bCompleted = in_array($bStatus['pretest'], ['completed', 'under_review']) 
                       && $bStatus['posttest'] === 'completed';
            
            // Priority 1: Active trainings (not completed) come first
            if (!$aCompleted && $bCompleted) return -1;
            if ($aCompleted && !$bCompleted) return 1;
            
            // Priority 2: Within same group, sort by start_date (newest first)
            $aStart = $a->start_date ?? '';
            $bStart = $b->start_date ?? '';
            return $bStart <=> $aStart; // DESC
        })->values();

        // Manual pagination
        $page = request()->get('page', 1);
        $perPage = 10;
        $total = $sorted->count();
        $items = $sorted->forPage($page, $perPage);

        $trainings = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('pages.training-test.training-test-list', [
            'trainings' => $trainings,
        ]);
    }
}
