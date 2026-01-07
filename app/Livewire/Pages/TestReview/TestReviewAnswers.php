<?php

namespace App\Livewire\Pages\TestReview;

use App\Models\Training;
use App\Models\Trainer;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestAttemptAnswer;
use App\Models\TestQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class TestReviewAnswers extends Component
{
  use Toast;

  public Training $training;
  public User $participant;

  // Selected test (pretest or posttest)
  public string $selectedTest = 'pretest';

  // Essay scores for grading (question_id => score)
  public array $essayScores = [];

  public function mount(Training $training, User $user)
  {
    $this->training = $training->load(['module.pretest.questions.options', 'module.posttest.questions.options']);
    $this->participant = $user;

    // Verify trainer has access
    $this->authorizeAccess();

    // Initialize essay scores
    $this->initializeEssayScores();
  }

  private function authorizeAccess(): void
  {
    $user = Auth::user();
    $trainerId = Trainer::where('user_id', $user->id)->value('id');

    if ($this->training->type === 'IN') {
      $hasAccess = $this->training->sessions()
        ->where('trainer_id', $trainerId)
        ->exists();

      if (!$hasAccess) {
        abort(403, 'You do not have access to review this training.');
      }
    }
  }

  private function initializeEssayScores(): void
  {
    // Get both pretest and posttest attempts
    $pretestAttempt = $this->getPretestAttempt();
    $posttestAttempt = $this->getPosttestAttempt();

    // Initialize pretest essay scores
    if ($pretestAttempt) {
      foreach ($pretestAttempt->answers as $answer) {
        if ($answer->question->question_type === 'essay') {
          $this->essayScores['pretest_' . $answer->question_id] = $answer->earned_points ?? 0;
        }
      }
    }

    // Initialize posttest essay scores
    if ($posttestAttempt) {
      foreach ($posttestAttempt->answers as $answer) {
        if ($answer->question->question_type === 'essay') {
          $this->essayScores['posttest_' . $answer->question_id] = $answer->earned_points ?? 0;
        }
      }
    }
  }

  public function getPretestAttempt(): ?TestAttempt
  {
    $pretest = $this->training->module?->pretest;
    if (!$pretest)
      return null;

    return TestAttempt::with(['answers.question.options', 'answers.selectedOption'])
      ->where('test_id', $pretest->id)
      ->where('user_id', $this->participant->id)
      ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
      ->latest()
      ->first();
  }

  public function getPosttestAttempt(): ?TestAttempt
  {
    $posttest = $this->training->module?->posttest;
    if (!$posttest)
      return null;

    return TestAttempt::with(['answers.question.options', 'answers.selectedOption'])
      ->where('test_id', $posttest->id)
      ->where('user_id', $this->participant->id)
      ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
      ->latest()
      ->first();
  }

  public function submitReview(): void
  {
    $attempt = $this->selectedTest === 'pretest'
      ? $this->getPretestAttempt()
      : $this->getPosttestAttempt();

    if (!$attempt) {
      $this->error('No test attempt found.');
      return;
    }

    $test = $attempt->test;

    DB::transaction(function () use ($attempt, $test) {
      $manualScore = 0;

      // Update essay scores
      foreach ($attempt->answers as $answer) {
        if ($answer->question->question_type === 'essay') {
          $scoreKey = $this->selectedTest . '_' . $answer->question_id;
          $score = (int) ($this->essayScores[$scoreKey] ?? 0);

          // Validate score doesn't exceed max points
          $maxPoints = $answer->question->max_points ?? 0;
          $score = min($score, $maxPoints);

          $answer->update([
            'earned_points' => $score,
          ]);

          $manualScore += $score;
        }
      }

      // Update attempt with manual score and recalculate total
      $attempt->manual_score = $manualScore;
      $attempt->total_score = $attempt->auto_score + $manualScore;
      $attempt->is_passed = $attempt->total_score >= $test->passing_score;
      $attempt->status = TestAttempt::STATUS_SUBMITTED;
      $attempt->save();
    });

    $this->success('Review submitted successfully.');
  }

  public function render()
  {
    $pretestAttempt = $this->getPretestAttempt();
    $posttestAttempt = $this->getPosttestAttempt();

    // Determine which tabs are available
    $hasPretest = $this->training->module?->pretest !== null && $pretestAttempt !== null;
    $hasPosttest = $this->training->module?->posttest !== null && $posttestAttempt !== null;

    // Auto-select first available tab
    if ($this->selectedTest === 'pretest' && !$hasPretest && $hasPosttest) {
      $this->selectedTest = 'posttest';
    } elseif ($this->selectedTest === 'posttest' && !$hasPosttest && $hasPretest) {
      $this->selectedTest = 'pretest';
    }

    $currentAttempt = $this->selectedTest === 'pretest' ? $pretestAttempt : $posttestAttempt;
    $currentTest = $this->selectedTest === 'pretest'
      ? $this->training->module?->pretest
      : $this->training->module?->posttest;

    // Get questions with answers for current test
    $questionsWithAnswers = [];
    if ($currentAttempt && $currentTest) {
      $questions = $currentTest->questions;
      $answersMap = $currentAttempt->answers->keyBy('question_id');

      foreach ($questions as $question) {
        $answer = $answersMap->get($question->id);
        $questionsWithAnswers[] = [
          'question' => $question,
          'answer' => $answer,
          'isEssay' => $question->question_type === 'essay',
          'isCorrect' => $answer?->is_correct ?? false,
          'earnedPoints' => $answer?->earned_points ?? 0,
          'maxPoints' => $question->max_points ?? 0,
        ];
      }
    }

    // Check if review has essay questions that need grading
    $hasEssayToGrade = collect($questionsWithAnswers)->contains('isEssay', true);
    $isUnderReview = $currentAttempt?->status === TestAttempt::STATUS_UNDER_REVIEW;

    return view('pages.test-review.test-review-answers', [
      'training' => $this->training,
      'participant' => $this->participant,
      'pretestAttempt' => $pretestAttempt,
      'posttestAttempt' => $posttestAttempt,
      'hasPretest' => $hasPretest,
      'hasPosttest' => $hasPosttest,
      'currentAttempt' => $currentAttempt,
      'currentTest' => $currentTest,
      'questionsWithAnswers' => $questionsWithAnswers,
      'hasEssayToGrade' => $hasEssayToGrade,
      'isUnderReview' => $isUnderReview,
    ]);
  }
}
