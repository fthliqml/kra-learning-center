<?php

namespace App\Livewire\Pages\TestReview;

use App\Models\Training;
use App\Models\Trainer;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TestReviewList extends Component
{
  use WithPagination;

  public string $search = '';
  public string $filterType = '';
  public string $filterStatus = '';

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedFilterType(): void
  {
    $this->resetPage();
  }

  public function updatedFilterStatus(): void
  {
    $this->resetPage();
  }

  /**
   * Get trainings that the current trainer can review:
   * - IN type: only trainings where trainer is assigned to sessions
   * - LMS type: all trainers can review
   */
  public function render()
  {
    $user = Auth::user();
    $trainerId = Trainer::where('user_id', $user->id)->value('id');

    $trainings = Training::with(['module.pretest.questions', 'module.posttest.questions', 'sessions.trainer', 'assessments'])
      ->whereIn('type', ['IN', 'LMS'])
      ->where(function ($query) use ($trainerId) {
        // IN type: trainer must be assigned to at least one session
        $query->where(function ($q) use ($trainerId) {
          $q->where('type', 'IN')
            ->whereHas('sessions', fn($s) => $s->where('trainer_id', $trainerId));
        })
          // LMS type: all trainers can review
          ->orWhere('type', 'LMS');
      })
      ->whereHas('module', function ($q) {
        // Must have pretest or posttest
        $q->where(function ($sub) {
          $sub->whereHas('pretest')
            ->orWhereHas('posttest');
        });
      })
      ->when($this->search, function ($q) {
        $q->where(function ($sub) {
          $sub->where('name', 'like', '%' . $this->search . '%')
            ->orWhereHas('module', fn($m) => $m->where('title', 'like', '%' . $this->search . '%'));
        });
      })
      ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
      ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
      ->orderBy('start_date', 'desc')
      ->paginate(10);

    // Add review stats to each training
    $trainings->getCollection()->transform(function ($training) {
      $training->reviewStats = $this->getReviewStats($training);
      return $training;
    });

    return view('pages.test-review.test-review-list', [
      'trainings' => $trainings,
      'typeOptions' => [
        ['value' => '', 'label' => 'All Types'],
        ['value' => 'IN', 'label' => 'In-House'],
        ['value' => 'LMS', 'label' => 'LMS'],
      ],
      'statusOptions' => [
        ['value' => '', 'label' => 'All Status'],
        ['value' => 'in_progress', 'label' => 'In Progress'],
        ['value' => 'done', 'label' => 'Done'],
        ['value' => 'approved', 'label' => 'Approved'],
      ],
    ]);
  }

  /**
   * Get review statistics for a training
   */
  private function getReviewStats(Training $training): array
  {
    $module = $training->module;
    if (!$module) {
      return [
        'totalParticipants' => 0,
        'needReview' => 0,
        'reviewed' => 0,
        'hasPretest' => false,
        'hasPosttest' => false,
      ];
    }

    $pretest = $module->pretest;
    $posttest = $module->posttest;

    $testIds = collect([$pretest?->id, $posttest?->id])->filter()->values()->all();

    if (empty($testIds)) {
      return [
        'totalParticipants' => $training->assessments->count(),
        'needReview' => 0,
        'reviewed' => 0,
        'hasPretest' => false,
        'hasPosttest' => false,
      ];
    }

    $needReview = TestAttempt::whereIn('test_id', $testIds)
      ->where('status', TestAttempt::STATUS_UNDER_REVIEW)
      ->count();

    $reviewed = TestAttempt::whereIn('test_id', $testIds)
      ->where('status', TestAttempt::STATUS_SUBMITTED)
      ->count();

    return [
      'totalParticipants' => $training->assessments->count(),
      'needReview' => $needReview,
      'reviewed' => $reviewed,
      'hasPretest' => $pretest !== null,
      'hasPosttest' => $posttest !== null,
    ];
  }
}
