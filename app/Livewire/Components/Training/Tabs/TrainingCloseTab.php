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

  public array $practicalGradeOptions = [
    ['value' => 'A', 'label' => 'A'],
    ['value' => 'B', 'label' => 'B'],
    ['value' => 'C', 'label' => 'C'],
    ['value' => 'D', 'label' => 'D'],
    ['value' => 'E', 'label' => 'E'],
  ];

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
        $rules["tempScores.{$id}.practical_grade"] = 'nullable|string|in:A,B,C,D,E';
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

    // Internal Training: sync pretest and posttest scores from web-based tests
    $this->syncInternalTrainingScores();
  }

  public function loadTempScores()
  {
    $assessments = TrainingAssessment::where('training_id', $this->trainingId)->get();
    foreach ($assessments as $assessment) {
      $grade = $assessment->practical_score !== null
        ? $this->getGradeFromScore((float) $assessment->practical_score)
        : null;

      $this->tempScores[$assessment->id] = [
        'pretest_score' => $assessment->pretest_score,
        'posttest_score' => $assessment->posttest_score,
        'practical_score' => $assessment->practical_score,
        'practical_grade' => $grade,
      ];
    }
  }

  protected function isLms(): bool
  {
    return strtoupper((string) ($this->training?->type ?? '')) === 'LMS';
  }

  /**
   * Convert numeric practical score to grade letter (A-E)
   */
  protected function getGradeFromScore(float $score): string
  {
    if ($score >= 90) return 'A';
    if ($score >= 81) return 'B';
    if ($score >= 71) return 'C';
    if ($score >= 61) return 'D';
    return 'E';
  }

  /**
   * Convert grade letter (A-E) to representative numeric score
   */
  protected function gradeToNumeric(?string $grade): ?float
  {
    $grade = strtoupper((string) $grade);

    return match ($grade) {
      'A' => 95.0,
      'B' => 85.0,
      'C' => 75.0,
      'D' => 65.0,
      'E' => 55.0,
      default => null,
    };
  }

  /**
   * Rank grade letter for comparison (higher is better)
   */
  protected function gradeRank(string $grade): int
  {
    return match (strtoupper($grade)) {
      'A' => 5,
      'B' => 4,
      'C' => 3,
      'D' => 2,
      'E' => 1,
      default => 0,
    };
  }

  /**
   * Check if a score value is valid (numeric and not empty)
   * Note: 0 is a valid score
   */
  protected function isValidScore($value): bool
  {
    if ($value === null) {
      return false;
    }

    if (is_string($value) && trim($value) === '') {
      return false;
    }

    return is_numeric($value);
  }

  /**
   * Get practical score as numeric from temp inputs.
   * UI stores practical as grade (A-E) in tempScores[...].practical_grade.
   */
  protected function getPracticalNumericScore(int $assessmentId, ?TrainingAssessment $assessment = null): ?float
  {
    $scores = $this->tempScores[$assessmentId] ?? [];

    $numeric = $scores['practical_score'] ?? null;
    $grade = $scores['practical_grade'] ?? null;

    if ($this->isValidScore($numeric)) {
      $numericFloat = (float) $numeric;
      $gradeFromNumeric = $this->getGradeFromScore($numericFloat);
      $gradeNormalized = strtoupper(trim((string) $grade));

      // If user changed grade in UI, prefer the grade selection.
      if ($gradeNormalized !== '' && $gradeNormalized !== $gradeFromNumeric) {
        $fromGrade = $this->gradeToNumeric($gradeNormalized);
        if ($fromGrade !== null) {
          return $fromGrade;
        }
      }

      return $numericFloat;
    }

    $fromGrade = $this->gradeToNumeric($grade);
    if ($fromGrade !== null) {
      return $fromGrade;
    }

    $existing = $assessment?->practical_score;
    if ($this->isValidScore($existing)) {
      return (float) $existing;
    }

    return null;
  }

  /**
   * Check if numeric practical score meets the module passing requirement.
   * Module practical passing score is stored as grade (A-E) in TrainingModule.
   */
  protected function passesPractical(?float $practicalScore, $passingScore): bool
  {
    if ($practicalScore === null) {
      return false;
    }

    if (is_numeric($passingScore)) {
      return $practicalScore >= (float) $passingScore;
    }

    $requiredGrade = strtoupper(trim((string) ($passingScore ?? '')));
    if ($requiredGrade === '') {
      return true;
    }

    $practicalGrade = $this->getGradeFromScore($practicalScore);

    return $this->gradeRank($practicalGrade) >= $this->gradeRank($requiredGrade);
  }

  /**
   * For LMS trainings, sync pretest and posttest scores from Course tests.
   * Theory score is derived from the Course posttest result (percent) and is not editable.
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

    // Get pretest and posttest for this course
    $pretest = Test::where('course_id', $courseId)->where('type', 'pretest')->select(['id', 'passing_score'])->first();
    $posttest = Test::where('course_id', $courseId)->where('type', 'posttest')->select(['id', 'passing_score'])->first();

    if (!$pretest && !$posttest) {
      return;
    }

    // Calculate max points for each test (MC + Essay)
    $pretestMaxPoints = $pretest ? (int) TestQuestion::where('test_id', $pretest->id)->sum('max_points') : 0;
    $posttestMaxPoints = $posttest ? (int) TestQuestion::where('test_id', $posttest->id)->sum('max_points') : 0;

    $assessments = TrainingAssessment::where('training_id', $this->trainingId)->select(['id', 'employee_id'])->get();
    if ($assessments->isEmpty()) {
      return;
    }

    $userIds = $assessments->pluck('employee_id')->filter()->unique()->values()->all();
    if (empty($userIds)) {
      return;
    }

    // Get latest pretest attempts (use highest score)
    $pretestAttemptsByUser = collect();
    if ($pretest) {
      $pretestAttempts = TestAttempt::where('test_id', $pretest->id)
        ->whereIn('user_id', $userIds)
        ->where('status', TestAttempt::STATUS_SUBMITTED)
        ->orderByDesc('total_score')
        ->orderByDesc('submitted_at')
        ->get(['id', 'user_id', 'auto_score', 'manual_score', 'total_score']);
      $pretestAttemptsByUser = $pretestAttempts->groupBy('user_id')->map(fn($rows) => $rows->first());
    }

    // Get latest posttest attempts (use highest score)
    $posttestAttemptsByUser = collect();
    if ($posttest) {
      $posttestAttempts = TestAttempt::where('test_id', $posttest->id)
        ->whereIn('user_id', $userIds)
        ->where('status', TestAttempt::STATUS_SUBMITTED)
        ->orderByDesc('total_score')
        ->orderByDesc('submitted_at')
        ->get(['id', 'user_id', 'auto_score', 'manual_score', 'total_score']);
      $posttestAttemptsByUser = $posttestAttempts->groupBy('user_id')->map(fn($rows) => $rows->first());
    }

    foreach ($assessments as $assessment) {
      $aid = (int) $assessment->id;
      if (!isset($this->tempScores[$aid])) {
        $this->tempScores[$aid] = [
          'pretest_score' => null,
          'posttest_score' => null,
          'practical_score' => null,
          'practical_grade' => null,
        ];
      }

      // Sync pretest score
      if ($pretest) {
        $pretestAttempt = $pretestAttemptsByUser->get($assessment->employee_id);
        if ($pretestAttempt) {
          $score = (int) ($pretestAttempt->auto_score ?? 0) + (int) ($pretestAttempt->manual_score ?? 0);
          $percent = $pretestMaxPoints > 0 ? (int) round(($score / $pretestMaxPoints) * 100) : 0;
          $this->tempScores[$aid]['pretest_score'] = $percent;
        }
      }

      // Sync posttest score
      if ($posttest) {
        $posttestAttempt = $posttestAttemptsByUser->get($assessment->employee_id);
        if (!$posttestAttempt) {
          $this->tempScores[$aid]['posttest_score'] = null;
        } else {
          $score = (int) ($posttestAttempt->auto_score ?? 0) + (int) ($posttestAttempt->manual_score ?? 0);
          $percent = $posttestMaxPoints > 0 ? (int) round(($score / $posttestMaxPoints) * 100) : 0;
          $this->tempScores[$aid]['posttest_score'] = $percent;
        }
      }

      // LMS has no practical score
      $this->tempScores[$aid]['practical_score'] = null;
      $this->tempScores[$aid]['practical_grade'] = null;
    }
  }

  /**
   * For Internal Training (IN type), sync pretest and posttest scores from web-based test attempts.
   * Only syncs scores when fully reviewed (STATUS_SUBMITTED).
   * For posttest: uses the highest score among all completed attempts.
   * For pretest: uses the highest score attempt.
   */
  protected function syncInternalTrainingScores(): void
  {
    if ($this->isLms()) {
      return;
    }

    $trainingType = strtoupper((string) ($this->training?->type ?? ''));
    if ($trainingType !== 'IN') {
      return;
    }

    // Training uses module_id, not training_module_id
    $moduleId = (int) ($this->training?->module_id ?? 0);
    if ($moduleId <= 0) {
      return;
    }

    // Get pretest and posttest for this module
    $pretest = Test::where('training_module_id', $moduleId)->where('type', 'pretest')->first();
    $posttest = Test::where('training_module_id', $moduleId)->where('type', 'posttest')->first();

    if (!$pretest && !$posttest) {
      return;
    }

    $assessments = TrainingAssessment::where('training_id', $this->trainingId)->select(['id', 'employee_id'])->get();
    if ($assessments->isEmpty()) {
      return;
    }

    $userIds = $assessments->pluck('employee_id')->filter()->unique()->values()->all();
    if (empty($userIds)) {
      return;
    }

    // Only sync fully reviewed attempts (STATUS_SUBMITTED)
    // Under review attempts have essay questions that haven't been graded yet

    // Get highest score attempt for pretest (fully reviewed only)
    $pretestAttemptsByUser = collect();
    if ($pretest) {
      $pretestAttempts = TestAttempt::where('test_id', $pretest->id)
        ->whereIn('user_id', $userIds)
        ->where('status', TestAttempt::STATUS_SUBMITTED) // Only fully reviewed
        ->orderByDesc('total_score')
        ->orderByDesc('submitted_at')
        ->orderByDesc('id')
        ->get(['id', 'user_id', 'total_score', 'is_passed', 'submitted_at']);

      $pretestAttemptsByUser = $pretestAttempts->groupBy('user_id')->map(fn($rows) => $rows->first());
    }

    // Get highest score attempt for posttest (fully reviewed only)
    $posttestBestScoreByUser = collect();
    if ($posttest) {
      $posttestAttempts = TestAttempt::where('test_id', $posttest->id)
        ->whereIn('user_id', $userIds)
        ->where('status', TestAttempt::STATUS_SUBMITTED) // Only fully reviewed
        ->orderByDesc('total_score')
        ->orderByDesc('submitted_at')
        ->orderByDesc('id')
        ->get(['id', 'user_id', 'total_score', 'is_passed', 'submitted_at']);

      $posttestBestScoreByUser = $posttestAttempts->groupBy('user_id')->map(fn($rows) => $rows->first());
    }

    foreach ($assessments as $assessment) {
      $aid = (int) $assessment->id;
      if (!isset($this->tempScores[$aid])) {
        $this->tempScores[$aid] = ['pretest_score' => null, 'posttest_score' => null, 'practical_score' => null];
      }

      // Sync pretest score from fully reviewed web test
      if ($pretest) {
        $pretestAttempt = $pretestAttemptsByUser->get($assessment->employee_id);
        if ($pretestAttempt) {
          $this->tempScores[$aid]['pretest_score'] = (int) ($pretestAttempt->total_score ?? 0);
        }
      }

      // Sync posttest score from fully reviewed web test (highest score)
      if ($posttest) {
        $posttestAttempt = $posttestBestScoreByUser->get($assessment->employee_id);
        if ($posttestAttempt) {
          $this->tempScores[$aid]['posttest_score'] = (int) ($posttestAttempt->total_score ?? 0);
        }
      }
    }
  }

  /**
   * Get test review status for each employee (for Internal Training)
   * Returns array with employee_id => ['pretest_need_review' => bool, 'posttest_need_review' => bool]
   */
  public function getTestReviewStatus(): array
  {
    $trainingType = strtoupper((string) ($this->training?->type ?? ''));
    if ($trainingType !== 'IN') {
      return [];
    }

    // Training uses module_id, not training_module_id
    $moduleId = (int) ($this->training?->module_id ?? 0);
    if ($moduleId <= 0) {
      return [];
    }

    $pretest = Test::where('training_module_id', $moduleId)->where('type', 'pretest')->first();
    $posttest = Test::where('training_module_id', $moduleId)->where('type', 'posttest')->first();

    if (!$pretest && !$posttest) {
      return [];
    }

    $assessments = TrainingAssessment::where('training_id', $this->trainingId)->select(['id', 'employee_id'])->get();
    $userIds = $assessments->pluck('employee_id')->filter()->unique()->values()->all();

    if (empty($userIds)) {
      return [];
    }

    $reviewStatus = [];

    // Check pretest under review status
    $pretestUnderReview = [];
    if ($pretest) {
      $pretestUnderReview = TestAttempt::where('test_id', $pretest->id)
        ->whereIn('user_id', $userIds)
        ->where('status', TestAttempt::STATUS_UNDER_REVIEW)
        ->pluck('user_id')
        ->unique()
        ->all();
    }

    // Check posttest under review status
    $posttestUnderReview = [];
    if ($posttest) {
      $posttestUnderReview = TestAttempt::where('test_id', $posttest->id)
        ->whereIn('user_id', $userIds)
        ->where('status', TestAttempt::STATUS_UNDER_REVIEW)
        ->pluck('user_id')
        ->unique()
        ->all();
    }

    foreach ($assessments as $assessment) {
      $reviewStatus[$assessment->employee_id] = [
        'pretest_need_review' => in_array($assessment->employee_id, $pretestUnderReview),
        'posttest_need_review' => in_array($assessment->employee_id, $posttestUnderReview),
      ];
    }

    return $reviewStatus;
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
        ['key' => 'pretest_score', 'label' => 'Pre-test Score', 'class' => '!text-center min-w-[120px]'],
        ['key' => 'posttest_score', 'label' => 'Post-test Score', 'class' => '!text-center min-w-[120px]'],
        ['key' => 'progress', 'label' => 'Progress', 'class' => '!text-center min-w-[120px]'],
        ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
      ];
    }

    return [
      ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
      ['key' => 'employee_name', 'label' => 'Employee Name', 'class' => 'min-w-[150px]'],
      ['key' => 'attendance_percentage', 'label' => 'Attendance', 'class' => '!text-center min-w-[110px]'],
      ['key' => 'pretest_score', 'label' => 'Pre-test Score', 'class' => '!text-center min-w-[120px]'],
      ['key' => 'posttest_score', 'label' => 'Post-test Score', 'class' => '!text-center min-w-[120px]'],
      ['key' => 'practical_score', 'label' => 'Practical Score', 'class' => '!text-center min-w-[120px]'],
      ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
      ['key' => 'actions', 'label' => '', 'class' => '!text-center w-10'],
    ];
  }

  public function assessments()
  {
    if (!$this->training) {
      return collect();
    }

    // Ensure LMS-derived theory score is always up-to-date when rendering
    $this->syncLmsScoresFromPosttest();

    // Ensure Internal Training scores are synced
    $this->syncInternalTrainingScores();

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
        $grade = $assessment->practical_score !== null
          ? $this->getGradeFromScore((float) $assessment->practical_score)
          : null;

        $this->tempScores[$assessment->id] = [
          'pretest_score' => $assessment->pretest_score,
          'posttest_score' => $assessment->posttest_score,
          'practical_score' => $assessment->practical_score,
          'practical_grade' => $grade,
        ];
      }

      // Use temp scores
      $pretestScore = $this->tempScores[$assessment->id]['pretest_score'];
      $posttestScore = $this->tempScores[$assessment->id]['posttest_score'];
      $practicalScore = $this->getPracticalNumericScore((int) $assessment->id, $assessment);

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
        $assessment->attendance_percentage = round(($presentCount / $totalSessions) * 100);
      } else {
        $assessment->attendance_percentage = 0;
      }

      // Calculate temp status
      $hasPosttest = is_numeric($posttestScore) && $posttestScore !== '';
      $attendancePassed = $assessment->attendance_percentage >= 75;

      if ($isLms) {
        // LMS has no attendance sessions, so skip attendance check
        if (!$hasPosttest) {
          $assessment->temp_status = 'pending';
        } else {
          $passing = (int) (Test::where('course_id', (int) ($this->training?->course_id ?? 0))
            ->where('type', 'posttest')
            ->value('passing_score') ?? 0);
          $assessment->temp_status = ($passing > 0 && (float) $posttestScore >= $passing) ? 'passed' : (($passing > 0) ? 'failed' : 'passed');
        }
      } else {
        $hasPractical = $practicalScore !== null;
        if (!$hasPosttest || !$hasPractical) {
          $assessment->temp_status = 'pending';
        } else {
          // Check attendance first
          if (!$attendancePassed) {
            $assessment->temp_status = 'failed';
          } else {
            $theoryPassingScore = $this->training->module->theory_passing_score ?? 60;
            $practicalPassingScore = $this->training->module->practical_passing_score ?? 'C';
            $theoryPassed = (float) $posttestScore >= $theoryPassingScore;
            $practicalPassed = $this->passesPractical((float) $practicalScore, $practicalPassingScore);
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
          $assessment->practical_score = $isLms
            ? null
            : $this->getPracticalNumericScore((int) $assessmentId, $assessment);

          // Calculate attendance percentage
          $totalSessions = $this->training->sessions()->count();
          $attendancePercentage = 0;
          $attendancePassed = false;
          if ($totalSessions > 0) {
            $sessionIds = $this->training->sessions()->pluck('id')->toArray();
            $presentCount = TrainingAttendance::whereIn('session_id', $sessionIds)
              ->where('employee_id', $assessment->employee_id)
              ->where('status', 'present')
              ->count();
            $attendancePercentage = round(($presentCount / $totalSessions) * 100, 2);
            $attendancePassed = $attendancePercentage >= 75;
          }

          // Save attendance percentage
          $assessment->attendance_percentage = $attendancePercentage;

          // Calculate status
          $posttest = $scores['posttest_score'];
          if ($isLms) {
            // LMS has no attendance sessions, so skip attendance check
            if (is_numeric($posttest)) {
              $passing = (int) (Test::where('course_id', (int) ($this->training?->course_id ?? 0))
                ->where('type', 'posttest')
                ->value('passing_score') ?? 0);
              $assessment->status = ($passing > 0 && (float) $posttest >= $passing) ? 'passed' : (($passing > 0) ? 'failed' : 'passed');
            } else {
              $assessment->status = 'pending';
            }
          } else {
            $practical = $this->getPracticalNumericScore((int) $assessmentId, $assessment);
            if ($this->isValidScore($posttest) && $practical !== null) {
              // Check attendance first
              if (!$attendancePassed) {
                $assessment->status = 'failed';
              } else {
                $theoryPassingScore = $this->training->module->theory_passing_score ?? 60;
                $practicalPassingScore = $this->training->module->practical_passing_score ?? 'C';
                $theoryPassed = (float) $posttest >= $theoryPassingScore;
                $practicalPassed = $this->passesPractical((float) $practical, $practicalPassingScore);
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
    $assessments = TrainingAssessment::where('training_id', $this->trainingId)
      ->with('employee')
      ->get();
    $missingScores = [];

    foreach ($assessments as $assessment) {
      $employeeName = $assessment->employee->name ?? 'Unknown';

      // Check from tempScores first, then fallback to database value
      $posttest = $this->tempScores[$assessment->id]['posttest_score'] ?? $assessment->posttest_score;

      // Check if posttest is filled - must be numeric (0 is valid, but null/empty is not)
      if (!$this->isValidScore($posttest)) {
        $missingScores[] = $employeeName . ' (Theory Score)';
      }

      // For non-LMS, also check practical score
      if (!$isLms) {
        $practical = $this->getPracticalNumericScore((int) $assessment->id, $assessment);
        if ($practical === null) {
          $missingScores[] = $employeeName . ' (Practical Score)';
        }
      }
    }

    if (!empty($missingScores)) {
      $message = 'The following scores must be completed before closing the training: ' . implode(', ', array_slice($missingScores, 0, 5));
      if (count($missingScores) > 5) {
        $message .= ' and ' . (count($missingScores) - 5) . ' more';
      }
      $this->error($message, position: 'toast-top toast-center');
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
          $assessment->practical_score = $isLms
            ? null
            : $this->getPracticalNumericScore((int) $assessmentId, $assessment);

          // Calculate attendance percentage
          $totalSessions = $this->training->sessions()->count();
          $attendancePercentage = 0;
          $attendancePassed = false;
          if ($totalSessions > 0) {
            $sessionIds = $this->training->sessions()->pluck('id')->toArray();
            $presentCount = TrainingAttendance::whereIn('session_id', $sessionIds)
              ->where('employee_id', $assessment->employee_id)
              ->where('status', 'present')
              ->count();
            $attendancePercentage = round(($presentCount / $totalSessions) * 100, 2);
            $attendancePassed = $attendancePercentage >= 75;
          }

          // Save attendance percentage
          $assessment->attendance_percentage = $attendancePercentage;

          // Calculate status
          $posttest = $assessment->posttest_score;
          if ($isLms) {
            // LMS has no attendance sessions, so skip attendance check
            if ($posttest === null || $posttest === '') {
              $assessment->status = 'pending';
            } else {
              $assessment->status = ($lmsPassingScore > 0 && (float) $posttest >= $lmsPassingScore)
                ? 'passed'
                : (($lmsPassingScore > 0) ? 'failed' : 'passed');
            }
          } else {
            $practical = $assessment->practical_score;
            if (!$this->isValidScore($posttest) || !$this->isValidScore($practical)) {
              $assessment->status = 'pending';
            } else {
              // Check attendance first
              if (!$attendancePassed) {
                $assessment->status = 'failed';
              } else {
                $theoryPassingScore = $this->training->module->theory_passing_score ?? 60;
                $practicalPassingScore = $this->training->module->practical_passing_score ?? 'C';
                $theoryPassed = (float) $posttest >= $theoryPassingScore;
                $practicalPassed = $this->passesPractical((float) $practical, $practicalPassingScore);
                $assessment->status = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
              }
            }
          }

          $assessment->save();
        }

        // Update training status to done
        $this->training->status = 'done';
        $this->training->save();

        // Auto-create Level 1 survey with default template (except LMS - no surveys)
        if (!$isLms) {
            $surveyService = new TrainingSurveyService();
            $surveyService->createSurveyForTraining($this->training);
        }
      });

      $successMessage = $isLms
          ? 'Training has been closed successfully.'
          : 'Training has been closed successfully. Survey Level 1 has been created for participants.';
      $this->success($successMessage, position: 'toast-top toast-center');
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
      'testReviewStatus' => $this->getTestReviewStatus(),
    ]);
  }
}
