<?php

namespace App\Livewire\Components\TrainingSchedule;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\TrainingAttendance; // only for delete cleanup
use Carbon\CarbonPeriod;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\TestAttempt;
use App\Models\User;

class DetailTrainingModal extends Component
{
    use Toast;

    public $modal = false;
    public $selectedEvent = null; // associative array (minimal training info)
    public $dayNumber = 1;
    public $activeTab = 'information';
    
    // Test Status Properties
    public $userId = null;
    public $testStatus = [];
    public $isEmployee = false;

    // Legacy edit modes removed; edits handled in child components

    protected $listeners = [
        'open-detail-training-modal' => 'open',
        'training-closed' => 'onTrainingClosed',
        'close-modal' => 'closeModal',
    ];

    public function triggerSaveDraft(): void
    {
        // Forward action to Close tab component
        $this->dispatch('training-close-save-draft');
    }

    public function triggerCloseTraining(): void
    {
        // Forward action to Close tab component
        $this->dispatch('training-close-close');
    }

    // Session helper methods removed; handled inside child tabs

    public function open($payload)
    {
        $this->resetModalState();
        if (!is_array($payload) || !isset($payload['id']))
            return;

        $this->selectedEvent = [
            'id' => $payload['id'],
            'name' => $payload['name'] ?? null,
            'group_comp' => $payload['group_comp'] ?? null,
            'type' => $payload['type'] ?? ($payload['training_type'] ?? null),
            'status' => $payload['status'] ?? null,
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
        ];

        // If calendar provided a specific day to open, set it now (validate range later)
        if (isset($payload['initial_day_number']) && is_numeric($payload['initial_day_number'])) {
            $this->dayNumber = max(1, (int) $payload['initial_day_number']);
        }
        $this->modal = true;
        
        // Calculate Test Status if user is employee
        $this->userId = Auth::id();
        /** @var User */
        $user = Auth::user();
        $this->isEmployee = $user && !$user->hasRole('admin');
        
        if ($this->isEmployee && isset($this->selectedEvent['id'])) {
            $this->loadTestStatus($this->selectedEvent['id']);
        }

        // Notify front-end that detail is ready (browser event). Fallback approach without dispatchBrowserEvent helper.
        if (method_exists($this, 'dispatchBrowserEvent')) {
            // Livewire v2 style
            $this->dispatchBrowserEvent('training-detail-ready');
        } else {
            // Livewire v3: emit to JS via dispatch + window listener (listen on 'training-detail-ready')
            $this->dispatch('training-detail-ready');
        }
    }

    protected function loadTestStatus(int $trainingId)
    {
        $training = Training::with([
            'module.pretest',
            'module.posttest',
            'course.tests',
        ])->find($trainingId);

        if (!$training) return;
        
        $this->testStatus = $this->getTestStatus($training, $this->userId);
    }

    // Copied from TrainingTestList.php - Consider refactoring to Service/Trait later
    protected function getTestStatus(Training $training, int $userId): array
    {
        $pretest = null;
        $posttest = null;

        if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
            $pretest = $training->course->tests->firstWhere('type', 'pretest');
            $posttest = $training->course->tests->firstWhere('type', 'posttest');
        } elseif ($training->type === 'IN' && $training->module) {
            $pretest = $training->module->pretest;
            $posttest = $training->module->posttest;
        }

        $pretestStatus = 'unavailable';
        $posttestStatus = 'unavailable';
        $pretestScore = null;
        $posttestScore = null;
        $posttestAttempts = 0;
        $posttestMaxAttempts = null;
        $posttestPassed = false;

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

        if ($posttest) {
            $posttestMaxAttempts = $posttest->max_attempts;

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

                $completedAttempts = $attempts->where('status', TestAttempt::STATUS_SUBMITTED);
                $posttestScore = $completedAttempts->max('total_score');

                if ($hasUnderReview) {
                    $posttestStatus = 'under_review';
                    $posttestScore = null;
                } elseif ($hasPassed) {
                    $posttestStatus = 'completed';
                } else {
                    $canRetake = !$posttestMaxAttempts || $posttestMaxAttempts == 0 || $posttestAttempts < $posttestMaxAttempts;
                    $posttestStatus = $canRetake ? 'retake' : 'failed';
                }
            } else {
                if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
                    $learningComplete = $training->course->hasCompletedLearningForUser($userId);
                    $posttestStatus = $learningComplete ? 'available' : 'locked';
                } else {
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

    public function startPreTest()
    {
        if (isset($this->selectedEvent['type']) && in_array($this->selectedEvent['type'], ['LMS', 'BLENDED'])) {
             // For LMS/BLENDED we might need training ID to link context, but course pretest usually keyed by course
             // But TrainingTestList uses courses-pretest.index with course param.
             // We need to fetch course_id. It's not in selectedEvent minimal payload.
             // Let's refetch training to be safe.
             $training = Training::find($this->selectedEvent['id']);
             if ($training && $training->course_id) {
                 $this->redirectRoute('courses-pretest.index', ['course' => $training->course_id], navigate: true);
             }
             return;
        }

        $this->redirectRoute('training-test.take', [
            'training' => $this->selectedEvent['id'], 
            'type' => 'pretest'
        ], navigate: true);
    }

    public function startPostTest()
    {
        if (isset($this->selectedEvent['type']) && in_array($this->selectedEvent['type'], ['LMS', 'BLENDED'])) {
             $training = Training::find($this->selectedEvent['id']);
             if ($training && $training->course_id) {
                 $this->redirectRoute('courses-posttest.index', ['course' => $training->course_id], navigate: true);
             }
             return;
        }

        $this->redirectRoute('training-test.take', [
            'training' => $this->selectedEvent['id'], 
            'type' => 'posttest'
        ], navigate: true);
    }

    public function resetModalState()
    {
        $this->modal = false;
        $this->selectedEvent = null;
        $this->dayNumber = 1;
        $this->activeTab = 'information';
    }

    // Legacy update & date parsing logic removed; handled inside child tabs

    public function updatedDayNumber()
    {
        $this->dayNumber = (int) $this->dayNumber;
        $this->dispatch('training-day-changed', $this->dayNumber);
    }

    public function trainingDates()
    {
        if (!$this->selectedEvent)
            return collect();
        $period = CarbonPeriod::create($this->selectedEvent['start_date'], $this->selectedEvent['end_date']);
        return collect($period)->map(function (\Carbon\Carbon $date, $index) {
            return [
                'id' => $index + 1,
                'name' => $date->format('d M Y'),
            ];
        })->values();
    }

    public function closeModal()
    {
        $this->modal = false;
    }

    public function onTrainingClosed($payload = null)
    {
        // Refresh the selected event if needed
        if ($payload && isset($payload['id']) && $this->selectedEvent && $this->selectedEvent['id'] == $payload['id']) {
            $this->selectedEvent['status'] = 'done';
        }
        // Close the modal
        $this->modal = false;
    }

    public function render()
    {
        return view('components.training-schedule.detail-training-modal', [
            'trainingDates' => $this->trainingDates(),
        ]);
    }
}
