<?php

namespace App\Livewire\Pages\TestReview;

use App\Models\Training;
use App\Models\Trainer;
use App\Models\TestAttempt;
use App\Models\TrainingAssessment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TestReviewParticipants extends Component
{
  use WithPagination;

  public Training $training;
  public string $search = '';
  public string $filterStatus = '';

  public function mount(Training $training)
  {
    // Load appropriate relationships based on training type
    if ($training->type === 'LMS') {
      $this->training = $training->load(['course.tests']);
    } else {
      $this->training = $training->load(['module.pretest', 'module.posttest']);
    }

    // Verify trainer has access to this training
    $this->authorizeAccess();
  }

  private function authorizeAccess(): void
  {
    $user = Auth::user();
    $trainerId = Trainer::where('user_id', $user->id)->value('id');

    if ($this->training->type === 'IN') {
      // For IN type, trainer must be assigned to at least one session
      $hasAccess = $this->training->sessions()
        ->where('trainer_id', $trainerId)
        ->exists();

      if (!$hasAccess) {
        abort(403, 'You do not have access to review this training.');
      }
    }
    // LMS type: all trainers can review (no additional check needed)
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedFilterStatus(): void
  {
    $this->resetPage();
  }

  public function render()
  {
    // Get pretest/posttest based on training type
    if ($this->training->type === 'LMS' && $this->training->course) {
      $pretest = $this->training->course->tests->firstWhere('type', 'pretest');
      $posttest = $this->training->course->tests->firstWhere('type', 'posttest');
    } else {
      $module = $this->training->module;
      $pretest = $module?->pretest;
      $posttest = $module?->posttest;
    }

    // Get participants with their test attempts
    $participants = TrainingAssessment::with(['employee'])
      ->where('training_id', $this->training->id)
      ->when($this->search, function ($q) {
        $q->whereHas('employee', function ($sub) {
          $sub->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('identification_number', 'like', '%' . $this->search . '%');
        });
      })
      ->paginate(15);

    // Add test status to each participant
    $participants->getCollection()->transform(function ($assessment) use ($pretest, $posttest) {
      $userId = $assessment->employee_id;

      $assessment->pretestStatus = $this->getTestAttemptStatus($pretest?->id, $userId);
      $assessment->posttestStatus = $this->getTestAttemptStatus($posttest?->id, $userId);

      // Filter by status if set
      if ($this->filterStatus === 'need_review') {
        $needsReview = ($assessment->pretestStatus['status'] ?? '') === 'under_review' ||
          ($assessment->posttestStatus['status'] ?? '') === 'under_review';
        if (!$needsReview) {
          return null;
        }
      } elseif ($this->filterStatus === 'reviewed') {
        $isReviewed = (($assessment->pretestStatus['status'] ?? '') === 'submitted' || !$pretest) &&
          (($assessment->posttestStatus['status'] ?? '') === 'submitted' || !$posttest);
        $hasTaken = ($assessment->pretestStatus['status'] ?? null) !== null ||
          ($assessment->posttestStatus['status'] ?? null) !== null;
        if (!$isReviewed || !$hasTaken) {
          return null;
        }
      } elseif ($this->filterStatus === 'not_taken') {
        $notTaken = ($assessment->pretestStatus['status'] ?? null) === null &&
          ($assessment->posttestStatus['status'] ?? null) === null;
        if (!$notTaken) {
          return null;
        }
      }

      return $assessment;
    });

    // Filter out nulls from status filter
    if ($this->filterStatus) {
      $participants->setCollection($participants->getCollection()->filter());
    }

    return view('pages.test-review.test-review-participants', [
      'training' => $this->training,
      'participants' => $participants,
      'hasPretest' => $pretest !== null,
      'hasPosttest' => $posttest !== null,
      'statusOptions' => [
        ['value' => '', 'label' => 'All Status'],
        ['value' => 'need_review', 'label' => 'Need Review'],
        ['value' => 'reviewed', 'label' => 'Reviewed'],
        ['value' => 'not_taken', 'label' => 'Not Taken'],
      ],
    ]);
  }

  /**
   * Get test attempt status for a user - supports multiple attempts
   */
  private function getTestAttemptStatus(?int $testId, int $userId): array
  {
    if (!$testId) {
      return ['status' => null, 'score' => null, 'attemptId' => null, 'totalAttempts' => 0, 'needReviewCount' => 0];
    }

    // Get all completed attempts (submitted or under_review)
    $attempts = TestAttempt::where('test_id', $testId)
      ->where('user_id', $userId)
      ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
      ->orderBy('attempt_number', 'desc')
      ->get();

    if ($attempts->isEmpty()) {
      return ['status' => null, 'score' => null, 'attemptId' => null, 'totalAttempts' => 0, 'needReviewCount' => 0];
    }

    // Count attempts needing review
    $needReviewCount = $attempts->where('status', TestAttempt::STATUS_UNDER_REVIEW)->count();

    // Get the latest attempt for display
    $latestAttempt = $attempts->first();

    // Get best score from all submitted attempts
    $bestScore = $attempts->where('status', TestAttempt::STATUS_SUBMITTED)->max('total_score');

    return [
      'status' => $latestAttempt->status,
      'score' => $latestAttempt->total_score,
      'bestScore' => $bestScore,
      'attemptId' => $latestAttempt->id,
      'attemptNumber' => $latestAttempt->attempt_number,
      'totalAttempts' => $attempts->count(),
      'needReviewCount' => $needReviewCount,
      'isPassed' => $latestAttempt->is_passed,
    ];
  }
}
