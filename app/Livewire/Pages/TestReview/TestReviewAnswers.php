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

    // Selected attempt ID for current test
    public ?int $selectedPretestAttemptId = null;
    public ?int $selectedPosttestAttemptId = null;

    // Essay scores for grading (question_id => score)
    public array $essayScores = [];

    // Track which essays have been explicitly scored by instructor
    public array $scoredEssays = [];

    // Modal state
    public bool $showConfirmModal = false;
    public array $unscoredQuestions = [];

    public function mount(Training $training, User $user)
    {
        $this->training = $training->load(['module.pretest.questions.options', 'module.posttest.questions.options']);
        $this->participant = $user;

        // Verify trainer has access
        $this->authorizeAccess();

        // Auto-select first available attempt for each test
        $this->initializeSelectedAttempts();

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

    private function initializeSelectedAttempts(): void
    {
        // Select the first under_review attempt, or latest if none need review
        $pretestAttempts = $this->getPretestAttempts();
        $posttestAttempts = $this->getPosttestAttempts();

        // For pretest: prefer under_review, then latest
        $pretestNeedReview = $pretestAttempts->firstWhere('status', TestAttempt::STATUS_UNDER_REVIEW);
        $this->selectedPretestAttemptId = $pretestNeedReview?->id ?? $pretestAttempts->first()?->id;

        // For posttest: prefer under_review, then latest
        $posttestNeedReview = $posttestAttempts->firstWhere('status', TestAttempt::STATUS_UNDER_REVIEW);
        $this->selectedPosttestAttemptId = $posttestNeedReview?->id ?? $posttestAttempts->first()?->id;
    }

    private function initializeEssayScores(): void
    {
        // Get both pretest and posttest attempts
        $pretestAttempt = $this->getSelectedPretestAttempt();
        $posttestAttempt = $this->getSelectedPosttestAttempt();

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

    /**
     * Get all pretest attempts for the participant
     */
    public function getPretestAttempts()
    {
        $pretest = $this->training->module?->pretest;
        if (!$pretest)
            return collect();

        return TestAttempt::where('test_id', $pretest->id)
            ->where('user_id', $this->participant->id)
            ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
            ->orderBy('attempt_number', 'desc')
            ->get();
    }

    /**
     * Get all posttest attempts for the participant
     */
    public function getPosttestAttempts()
    {
        $posttest = $this->training->module?->posttest;
        if (!$posttest)
            return collect();

        return TestAttempt::where('test_id', $posttest->id)
            ->where('user_id', $this->participant->id)
            ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
            ->orderBy('attempt_number', 'desc')
            ->get();
    }

    /**
     * Get currently selected pretest attempt with answers
     */
    public function getSelectedPretestAttempt(): ?TestAttempt
    {
        if (!$this->selectedPretestAttemptId)
            return null;

        return TestAttempt::with(['answers.question.options', 'answers.selectedOption'])
            ->find($this->selectedPretestAttemptId);
    }

    /**
     * Get currently selected posttest attempt with answers
     */
    public function getSelectedPosttestAttempt(): ?TestAttempt
    {
        if (!$this->selectedPosttestAttemptId)
            return null;

        return TestAttempt::with(['answers.question.options', 'answers.selectedOption'])
            ->find($this->selectedPosttestAttemptId);
    }

    /**
     * When attempt selection changes, reinitialize essay scores
     */
    public function updatedSelectedPretestAttemptId(): void
    {
        $attempt = $this->getSelectedPretestAttempt();
        if ($attempt) {
            foreach ($attempt->answers as $answer) {
                if ($answer->question->question_type === 'essay') {
                    $this->essayScores['pretest_' . $answer->question_id] = $answer->earned_points ?? 0;
                }
            }
        }
        // Reset scored tracking when changing attempt
        $this->scoredEssays = [];
    }

    public function updatedSelectedPosttestAttemptId(): void
    {
        $attempt = $this->getSelectedPosttestAttempt();
        if ($attempt) {
            foreach ($attempt->answers as $answer) {
                if ($answer->question->question_type === 'essay') {
                    $this->essayScores['posttest_' . $answer->question_id] = $answer->earned_points ?? 0;
                }
            }
        }
        // Reset scored tracking when changing attempt
        $this->scoredEssays = [];
    }

    /**
     * Track when instructor explicitly scores an essay
     */
    public function updatedEssayScores($value, $key): void
    {
        $this->scoredEssays[$key] = true;
    }

    /**
     * Open confirmation modal, checking for unscored essays
     */
    public function openConfirmModal(): void
    {
        $this->unscoredQuestions = $this->getUnscoredEssayQuestions();
        $this->showConfirmModal = true;
    }

    /**
     * Close confirmation modal
     */
    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->unscoredQuestions = [];
    }

    /**
     * Get list of essay questions that haven't been explicitly scored
     */
    private function getUnscoredEssayQuestions(): array
    {
        $attempt = $this->selectedTest === 'pretest'
            ? $this->getSelectedPretestAttempt()
            : $this->getSelectedPosttestAttempt();

        if (!$attempt)
            return [];

        $unscored = [];
        $questionNumber = 0;

        foreach ($attempt->test->questions as $question) {
            $questionNumber++;
            if ($question->question_type === 'essay') {
                $scoreKey = $this->selectedTest . '_' . $question->id;

                // Check if this essay was explicitly scored during this session
                if (!isset($this->scoredEssays[$scoreKey])) {
                    // Also check if it has a non-zero score from before (already graded)
                    $answer = $attempt->answers->firstWhere('question_id', $question->id);
                    if ($answer && $answer->earned_points > 0) {
                        // Already has a score, consider it scored
                        continue;
                    }

                    $unscored[] = [
                        'number' => $questionNumber,
                        'preview' => \Illuminate\Support\Str::limit(strip_tags($question->text), 50),
                    ];
                }
            }
        }

        return $unscored;
    }

    public function submitReview(): void
    {
        $attempt = $this->selectedTest === 'pretest'
            ? $this->getSelectedPretestAttempt()
            : $this->getSelectedPosttestAttempt();

        if (!$attempt) {
            $this->error('No test attempt found.', position: 'toast-top toast-center');
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

        $this->closeConfirmModal();
        $this->success('Review submitted successfully.', position: 'toast-top toast-center');
    }

    public function render()
    {
        $pretestAttempts = $this->getPretestAttempts();
        $posttestAttempts = $this->getPosttestAttempts();

        $pretestAttempt = $this->getSelectedPretestAttempt();
        $posttestAttempt = $this->getSelectedPosttestAttempt();

        // Determine which tabs are available
        $hasPretest = $this->training->module?->pretest !== null && $pretestAttempts->isNotEmpty();
        $hasPosttest = $this->training->module?->posttest !== null && $posttestAttempts->isNotEmpty();

        // Auto-select first available tab
        if ($this->selectedTest === 'pretest' && !$hasPretest && $hasPosttest) {
            $this->selectedTest = 'posttest';
        } elseif ($this->selectedTest === 'posttest' && !$hasPosttest && $hasPretest) {
            $this->selectedTest = 'pretest';
        }

        $currentAttempt = $this->selectedTest === 'pretest' ? $pretestAttempt : $posttestAttempt;
        $currentAttempts = $this->selectedTest === 'pretest' ? $pretestAttempts : $posttestAttempts;
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

        // Build attempt options for dropdown (only for posttest - pretest is single attempt)
        $attemptOptions = [];
        if ($this->selectedTest === 'posttest') {
            $attemptOptions = $posttestAttempts->map(function ($attempt) {
                $statusLabel = $attempt->status === TestAttempt::STATUS_UNDER_REVIEW ? ' (Need Review)' : '';
                return [
                    'value' => $attempt->id,
                    'label' => "Attempt #{$attempt->attempt_number} - {$attempt->total_score}%{$statusLabel}",
                ];
            })->all();
        }

        return view('pages.test-review.test-review-answers', [
            'training' => $this->training,
            'participant' => $this->participant,
            'pretestAttempts' => $pretestAttempts,
            'posttestAttempts' => $posttestAttempts,
            'hasPretest' => $hasPretest,
            'hasPosttest' => $hasPosttest,
            'currentAttempt' => $currentAttempt,
            'currentAttempts' => $currentAttempts,
            'currentTest' => $currentTest,
            'questionsWithAnswers' => $questionsWithAnswers,
            'hasEssayToGrade' => $hasEssayToGrade,
            'isUnderReview' => $isUnderReview,
            'attemptOptions' => $attemptOptions,
        ]);
    }
}
