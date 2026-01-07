<?php

namespace App\Livewire\Pages\TrainingTest;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Models\Training;
use App\Models\TrainingAssessment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class TakeTrainingTest extends Component
{
  use Toast;

  public Training $training;
  public ?Test $test = null;
  public string $testType; // 'pretest' or 'posttest'
  public array $questions = [];
  public array $answers = [];
  public array $errorQuestionIndexes = [];

  public function mount(Training $training, string $type)
  {
    $userId = Auth::id();
    $this->testType = $type;

    // Verify user is assigned to this training
    $isAssigned = TrainingAssessment::where('training_id', $training->id)
      ->where('employee_id', $userId)
      ->exists();

    if (!$isAssigned) {
      abort(403, 'You are not assigned to this training.');
    }

    // Verify training is IN type
    if ($training->type !== 'IN') {
      abort(400, 'Only IN-type trainings have online tests.');
    }

    // Load training with module
    $this->training = $training->load('module');

    if (!$this->training->module) {
      abort(400, 'Training module not configured.');
    }

    // Load the test
    $this->test = Test::with([
      'questions' => fn($q) => $q->orderBy('order'),
      'questions.options' => fn($q) => $q->orderBy('order'),
    ])
      ->where('training_module_id', $this->training->module->id)
      ->where('type', $type)
      ->first();

    if (!$this->test) {
      abort(404, ucfirst($type) . ' not configured for this training module.');
    }

    // Check if posttest but pretest not completed
    if ($type === 'posttest') {
      $pretest = Test::where('training_module_id', $this->training->module->id)
        ->where('type', 'pretest')
        ->first();

      if ($pretest) {
        $pretestCompleted = TestAttempt::where('test_id', $pretest->id)
          ->where('user_id', $userId)
          ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
          ->exists();

        if (!$pretestCompleted) {
          return redirect()->route('training-test.take', [
            'training' => $training->id,
            'type' => 'pretest',
          ])->with('info', 'Please complete the pretest first.');
        }
      }
    }

    // Check test attempt eligibility
    $submittedAttempts = TestAttempt::where('test_id', $this->test->id)
      ->where('user_id', $userId)
      ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
      ->orderBy('attempt_number', 'desc')
      ->get();

    if ($submittedAttempts->isNotEmpty()) {
      $latestAttempt = $submittedAttempts->first();

      // PRETEST: Only 1 attempt allowed (always)
      if ($type === 'pretest') {
        return redirect()->route('training-test.index')
          ->with('info', 'You have already completed the pretest.');
      }

      // POSTTEST: Check if passed or max attempts reached
      if ($type === 'posttest') {
        // If any attempt is still under review, wait for review result
        $hasUnderReview = $submittedAttempts->contains('status', TestAttempt::STATUS_UNDER_REVIEW);
        if ($hasUnderReview) {
          return redirect()->route('training-test.index')
            ->with('info', 'Your previous posttest is still under review. Please wait for the result.');
        }

        // If already passed, no need to retake
        $hasPassed = $submittedAttempts->contains('is_passed', true);
        if ($hasPassed) {
          return redirect()->route('training-test.index')
            ->with('info', 'You have already passed the posttest.');
        }

        // Check max attempts limit (null or 0 = unlimited)
        $maxAttempts = $this->test->max_attempts;
        $attemptCount = $submittedAttempts->count();

        if ($maxAttempts && $maxAttempts > 0 && $attemptCount >= $maxAttempts) {
          return redirect()->route('training-test.index')
            ->with('info', 'You have reached the maximum number of attempts (' . $maxAttempts . ') for this posttest.');
        }
      }
    }

    // Prepare questions
    $collection = $this->test->questions->map(function ($q) {
      return [
        'id' => $q->id,
        'type' => $q->question_type,
        'text' => $q->text,
        'options' => $q->question_type === 'multiple'
          ? $q->options->map(fn($o) => [
            'id' => $o->id,
            'text' => $o->text,
          ])->values()->all()
          : [],
      ];
    });

    // Randomize if configured
    if ($this->test->randomize_question) {
      $collection = $collection->shuffle();
    }

    $this->questions = $collection->values()->all();

    // Initialize answers array
    foreach ($this->questions as $q) {
      $this->answers[$q['id']] = null;
    }
  }

  public function submit()
  {
    $userId = Auth::id();
    if (!$userId)
      abort(401);

    // Validate all questions answered
    $this->errorQuestionIndexes = [];
    $errors = [];

    foreach ($this->questions as $index => $q) {
      $answer = $this->answers[$q['id']] ?? null;
      if ($answer === null || $answer === '') {
        $this->errorQuestionIndexes[] = $index;
        $errors[] = "Question " . ($index + 1) . " is not answered";
      }
    }

    if (!empty($errors)) {
      $this->error(
        'Please answer all questions before submitting.',
        timeout: 6000,
        position: 'toast-top toast-center'
      );
      return;
    }

    // Load questions for grading
    $questionRows = TestQuestion::where('test_id', $this->test->id)
      ->get()
      ->keyBy('id');

    // Get selected option IDs for MC questions
    $selectedOptionIds = [];
    foreach ($questionRows as $qid => $q) {
      if ($q->question_type === 'multiple' && isset($this->answers[$qid])) {
        $selectedOptionIds[] = (int) $this->answers[$qid];
      }
    }

    $optionsById = collect();
    if (!empty($selectedOptionIds)) {
      $optionsById = TestQuestionOption::whereIn('id', $selectedOptionIds)
        ->get()
        ->keyBy('id');
    }

    // Check for essay questions
    $hasEssay = $questionRows->contains(fn($q) => $q->question_type !== 'multiple');

    $attemptNumber = (int) (TestAttempt::where('test_id', $this->test->id)
      ->where('user_id', $userId)
      ->max('attempt_number') ?? 0) + 1;

    $totalScore = 0;
    $maxScore = 0;

    DB::transaction(function () use ($userId, $questionRows, $optionsById, $attemptNumber, $hasEssay, &$totalScore, &$maxScore) {
      $now = now();

      $attempt = TestAttempt::create([
        'user_id' => $userId,
        'test_id' => $this->test->id,
        'attempt_number' => $attemptNumber,
        'status' => $hasEssay ? TestAttempt::STATUS_UNDER_REVIEW : TestAttempt::STATUS_SUBMITTED,
        'auto_score' => 0,
        'manual_score' => 0,
        'total_score' => 0,
        'is_passed' => false,
        'started_at' => $now,
        'submitted_at' => $now,
      ]);

      $autoScore = 0;

      foreach ($this->answers as $qid => $value) {
        $q = $questionRows->get($qid);
        if (!$q)
          continue;

        $maxScore += (int) ($q->max_points ?? 1);

        $selectedOptionId = null;
        $essayAnswer = null;
        $isCorrect = null;
        $earned = 0;

        if ($q->question_type === 'multiple') {
          $selectedOptionId = (int) $value;
          $opt = $optionsById->get($selectedOptionId);

          if ($opt && $opt->is_correct) {
            $earned = (int) ($q->max_points ?? 1);
            $isCorrect = true;
          } else {
            $earned = 0;
            $isCorrect = false;
          }
          $autoScore += $earned;
        } else {
          $essayAnswer = (string) $value;
        }

        \App\Models\TestAttemptAnswer::create([
          'attempt_id' => $attempt->id,
          'question_id' => $qid,
          'selected_option_id' => $selectedOptionId,
          'essay_answer' => $essayAnswer,
          'is_correct' => $isCorrect,
          'points_earned' => $earned,
        ]);
      }

      // Calculate percentage score
      $totalScore = $maxScore > 0 ? round(($autoScore / $maxScore) * 100, 1) : 0;

      $attempt->update([
        'auto_score' => $autoScore,
        'total_score' => $totalScore,
        'is_passed' => $totalScore >= ($this->test->passing_score ?? 75),
      ]);

      // Update TrainingAssessment with test score (only for auto-scored tests)
      $assessment = TrainingAssessment::where('training_id', $this->training->id)
        ->where('employee_id', $userId)
        ->first();

      if ($assessment && !$hasEssay) {
        $scoreField = $this->testType . '_score';
        $assessment->update([
          $scoreField => $totalScore,
        ]);
      }
    });

    if ($hasEssay) {
      $this->success(
        'Test submitted successfully! Your answers will be reviewed by an instructor.',
        timeout: 5000,
        position: 'toast-top toast-center'
      );
    } else {
      $this->success(
        'Test submitted successfully! Your score: ' . $totalScore . '%',
        timeout: 5000,
        position: 'toast-top toast-center'
      );
    }

    return redirect()->route('training-test.index');
  }

  public function render()
  {
    return view('pages.training-test.take-training-test', [
      'training' => $this->training,
      'test' => $this->test,
      'testType' => $this->testType,
      'questions' => $this->questions,
    ]);
  }
}
