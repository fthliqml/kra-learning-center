<?php

namespace App\Livewire\Pages\Survey;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\TrainingSurvey;

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
    $user = Auth::user();
    if (!$user) {
      return collect(); // atau paginator kosong
    }

    // Ambil survey untuk employee berdasarkan training assessments.
    // NOTE: Jangan mengharuskan survey_responses sudah ada, karena response bisa dibuat saat submit.
    $base = TrainingSurvey::query()
      ->forEmployee($user->id)
      ->with([
        'training',
        // My response only (for badge/status)
        'surveyResponses' => function ($q) use ($user) {
          $q->where('employee_id', $user->id);
        }
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

      if ($status === 'complete') {
        $survey->badge_label = 'Complete';
        $survey->badge_class = 'badge-primary bg-primary/95';
      } elseif ($isDraft || !$trainingReady) {
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
      $survey->start_disabled = $isDraft || !$trainingReady;

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
