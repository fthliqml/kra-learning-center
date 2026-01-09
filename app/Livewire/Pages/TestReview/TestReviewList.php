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

    $trainings = Training::with(['module.pretest.questions', 'module.posttest.questions', 'course.tests.questions', 'sessions.trainer', 'assessments'])
      ->whereIn('type', ['IN', 'LMS'])
      // Filter by start date: IN shows H-1 (day before), LMS shows on hari H (start_date)
      ->where(function ($query) {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        
        // IN type: show from H-1 (start_date <= tomorrow)
        $query->where(function ($q) use ($tomorrow) {
          $q->where('type', 'IN')
            ->where('start_date', '<=', $tomorrow);
        })
        // LMS type: show from hari H (start_date <= today)
        ->orWhere(function ($q) use ($today) {
          $q->where('type', 'LMS')
            ->where('start_date', '<=', $today);
        });
      })
      ->where(function ($query) use ($trainerId) {
        // IN type: trainer must be assigned to at least one session
        $query->where(function ($q) use ($trainerId) {
          $q->where('type', 'IN')
            ->whereHas('sessions', fn($s) => $s->where('trainer_id', $trainerId));
        })
          // LMS type: all trainers can review
          ->orWhere('type', 'LMS');
      })
      ->where(function ($query) {
        // IN type: check tests via module (TrainingModule)
        $query->where(function ($q) {
          $q->where('type', 'IN')
            ->whereHas('module', function ($m) {
              $m->where(function ($sub) {
                $sub->whereHas('pretest')
                  ->orWhereHas('posttest');
              });
            });
        })
          // LMS type: check tests via course
          ->orWhere(function ($q) {
            $q->where('type', 'LMS')
              ->whereHas('course', function ($c) {
                $c->whereHas('tests');
              });
          });
      })
      ->when($this->search, function ($q) {
        $q->where(function ($sub) {
          $sub->where('name', 'like', '%' . $this->search . '%')
            ->orWhereHas('module', fn($m) => $m->where('title', 'like', '%' . $this->search . '%'))
            ->orWhereHas('course', fn($c) => $c->where('title', 'like', '%' . $this->search . '%'));
        });
      })
      ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
      ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
      ->get();

    // Add review stats and latest submission info to each training
    $trainings->transform(function ($training) {
      $training->reviewStats = $this->getReviewStats($training);
      $training->latestPendingSubmission = $this->getLatestPendingSubmission($training);
      return $training;
    });

    // Smart sorting: trainings with pending reviews first (by latest submission date), 
    // then trainings without pending reviews (by start_date)
    $sorted = $trainings->sort(function ($a, $b) {
      $aHasPending = ($a->reviewStats['needReview'] ?? 0) > 0;
      $bHasPending = ($b->reviewStats['needReview'] ?? 0) > 0;

      // Priority 1: Pending reviews come first
      if ($aHasPending && !$bHasPending) return -1;
      if (!$aHasPending && $bHasPending) return 1;

      // Priority 2: Within same group, sort appropriately
      if ($aHasPending && $bHasPending) {
        // Both have pending: sort by latest submission date (newest first)
        $aDate = $a->latestPendingSubmission;
        $bDate = $b->latestPendingSubmission;
        return $bDate <=> $aDate; // DESC
      } else {
        // Neither have pending: sort by start_date (newest first)
        $aStart = $a->start_date ?? '';
        $bStart = $b->start_date ?? '';
        return $bStart <=> $aStart; // DESC
      }
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
    $pretest = null;
    $posttest = null;

    // Get pretest/posttest based on training type
    if ($training->type === 'LMS' && $training->course) {
      // LMS uses course->tests
      $pretest = $training->course->tests->firstWhere('type', 'pretest');
      $posttest = $training->course->tests->firstWhere('type', 'posttest');
    } elseif ($training->type === 'IN' && $training->module) {
      // IN uses module->pretest/posttest
      $pretest = $training->module->pretest;
      $posttest = $training->module->posttest;
    }

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

  /**
   * Get the latest pending submission date for a training's tests
   */
  private function getLatestPendingSubmission(Training $training): ?string
  {
    $pretest = null;
    $posttest = null;

    // Get pretest/posttest based on training type (same logic as getReviewStats)
    if ($training->type === 'LMS' && $training->course) {
      $pretest = $training->course->tests->firstWhere('type', 'pretest');
      $posttest = $training->course->tests->firstWhere('type', 'posttest');
    } elseif ($training->type === 'IN' && $training->module) {
      $pretest = $training->module->pretest;
      $posttest = $training->module->posttest;
    }

    $testIds = collect([$pretest?->id, $posttest?->id])->filter()->values()->all();

    if (empty($testIds)) {
      return null;
    }

    return TestAttempt::whereIn('test_id', $testIds)
      ->where('status', TestAttempt::STATUS_UNDER_REVIEW)
      ->max('submitted_at');
  }
}
