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

  public function getTestStatus(Training $training, int $userId): array
  {
    $module = $training->module;
    if (!$module) {
      return [
        'pretest' => 'unavailable',
        'posttest' => 'unavailable',
        'pretestScore' => null,
        'posttestScore' => null,
      ];
    }

    $pretest = $module->pretest;
    $posttest = $module->posttest;

    $pretestStatus = 'unavailable';
    $posttestStatus = 'unavailable';
    $pretestScore = null;
    $posttestScore = null;

    if ($pretest) {
      $attempt = TestAttempt::where('test_id', $pretest->id)
        ->where('user_id', $userId)
        ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
        ->latest()
        ->first();

      if ($attempt) {
        $pretestStatus = 'completed';
        $pretestScore = $attempt->total_score;
      } else {
        $pretestStatus = 'available';
      }
    }

    if ($posttest) {
      $attempt = TestAttempt::where('test_id', $posttest->id)
        ->where('user_id', $userId)
        ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
        ->latest()
        ->first();

      if ($attempt) {
        $posttestStatus = 'completed';
        $posttestScore = $attempt->total_score;
      } else {
        // Posttest available only after pretest completed
        $posttestStatus = ($pretestStatus === 'completed') ? 'available' : 'locked';
      }
    }

    return [
      'pretest' => $pretestStatus,
      'posttest' => $posttestStatus,
      'pretestScore' => $pretestScore,
      'posttestScore' => $posttestScore,
    ];
  }

  public function render()
  {
    $userId = Auth::id();

    // Get IN-type trainings where user is assigned
    $trainings = Training::with(['module.pretest', 'module.posttest', 'competency'])
      ->where('type', 'IN')
      ->whereIn('status', ['pending', 'ongoing'])
      ->whereHas('assessments', fn($q) => $q->where('employee_id', $userId))
      ->when($this->search, function ($q) {
        $q->where(function ($sub) {
          $sub->where('name', 'like', '%' . $this->search . '%')
            ->orWhereHas('module', fn($m) => $m->where('title', 'like', '%' . $this->search . '%'));
        });
      })
      ->orderBy('start_date', 'desc')
      ->paginate(10);

    // Add test status to each training
    $trainings->getCollection()->transform(function ($training) use ($userId) {
      $training->testStatus = $this->getTestStatus($training, $userId);
      return $training;
    });

    return view('pages.training-test.training-test-list', [
      'trainings' => $trainings,
    ]);
  }
}
