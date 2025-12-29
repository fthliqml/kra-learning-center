<?php

namespace App\Livewire\Components\Dashboard;

use App\Models\TrainingSurvey;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PendingSurveys extends Component
{
  public $surveys = [];

  public function mount()
  {
    $this->loadPendingSurveys();
  }

  public function loadPendingSurveys()
  {
    $user = Auth::user();
    if (!$user) {
      $this->surveys = [];
      return;
    }

    // Get pending surveys for employee (Level 1 only - immediate after training)
    // - Survey must be published (not draft)
    // - Training must be done or approved
    // - Employee has not completed the survey yet
    $this->surveys = TrainingSurvey::query()
      ->forEmployee($user->id)
      ->where('level', 1) // Only level 1 surveys for immediate feedback
      ->where('status', '!=', 'draft')
      ->whereHas('training', function ($q) {
        $q->whereIn('status', ['done', 'approved']);
      })
      ->with([
        'training',
        'surveyResponses' => function ($q) use ($user) {
          $q->where('employee_id', $user->id);
        }
      ])
      ->get()
      ->filter(function ($survey) use ($user) {
        // Filter to only show surveys not completed by this user
        $myResponse = $survey->surveyResponses->first();
        return !$myResponse || !$myResponse->is_completed;
      })
      ->map(function ($survey) {
        $myResponse = $survey->surveyResponses->first();
        return [
          'id' => $survey->id,
          'training_id' => $survey->training_id,
          'training_name' => $survey->training?->name ?? 'Unknown Training',
          'level' => $survey->level,
          'status' => $myResponse ? ($myResponse->is_completed ? 'complete' : 'incomplete') : 'not_started',
          'created_at' => $survey->created_at?->diffForHumans(),
        ];
      })
      ->values()
      ->take(5) // Limit to 5 pending surveys
      ->toArray();
  }

  public function render()
  {
    return view('components.dashboard.pending-surveys');
  }
}
