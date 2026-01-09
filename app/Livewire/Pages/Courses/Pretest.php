<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestAttemptAnswer;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Pretest extends Component
{
    public Course $course;
    public ?Test $pretest = null;
    public array $questions = [];

    public bool $isReviewMode = false;
    public ?TestAttempt $attempt = null;
    public array $attemptAnswers = [];

    public function mount(Course $course)
    {
        $userId = Auth::id();
        // Assigned via TrainingAssessment within the training schedule window
        $today = now()->startOfDay();
        $assigned = $course->trainings()
            // ->where(function ($w) use ($today) {
            //     $w->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            // })
            // ->where(function ($w) use ($today) {
            //     $w->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            // })
            ->whereHas('assessments', function ($a) use ($userId) {
                $a->where('employee_id', $userId);
            })
            ->exists();
        if (!$assigned) {
            abort(403, 'You are not assigned to this course.');
        }

        // Gate: before schedule start, user can only view overview
        if ($userId && !$course->isAvailableForUser($userId)) {
            return redirect()->route('courses-overview.show', ['course' => $course->id]);
        }

        // Ensure enrollment exists and mark status in_progress on first engagement
        if ($userId) {
            $enrollment = $course->userCourses()->firstOrCreate(
                ['user_id' => $userId],
                ['status' => 'in_progress', 'current_step' => 0]
            );
            if (($enrollment->status ?? '') === '' || strtolower($enrollment->status) === 'not_started') {
                $enrollment->status = 'in_progress';
                $enrollment->save();
            }
        }

        // Eager load learning modules for sidebar listing
        $this->course = $course->load(['learningModules' => fn($q) => $q->orderBy('id')]);

        // Load pretest with minimal fields (questions & options) once
        $this->pretest = Test::with([
            'questions' => function ($q) {
                $q->select('id', 'test_id', 'question_type', 'text', 'max_points');
            },
            'questions.options' => function ($q) {
                $q->select('id', 'question_id', 'text', 'is_correct');
            },
        ])
            ->where('course_id', $this->course->id)
            ->where('type', 'pretest')
            ->first();

        if ($this->pretest) {
            // Check if already submitted an attempt
            $existingAttempt = TestAttempt::where('test_id', $this->pretest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
                ->orderByDesc('submitted_at')
                ->first();
            
            // Check if user has posttest attempt (remedial mode)
            $posttest = Test::where('course_id', $course->id)->where('type', 'posttest')->select('id')->first();
            $hasPosttestAttempt = false;
            if ($posttest) {
                $hasPosttestAttempt = TestAttempt::where('test_id', $posttest->id)
                    ->where('user_id', $userId)
                    ->exists();
            }
            
            if ($existingAttempt) {
                if ($hasPosttestAttempt) {
                    // Review mode: show pretest result with answers
                    $this->isReviewMode = true;
                    $this->attempt = $existingAttempt;
                    
                    // Load attempt answers with question and option details
                    $this->attemptAnswers = TestAttemptAnswer::where('attempt_id', $existingAttempt->id)
                        ->get()
                        ->keyBy('question_id')
                        ->toArray();
                } else {
                    // Normal mode: redirect to modules
                    return redirect()->route('courses-modules.index', ['course' => $course->id]);
                }
            }

            $collection = $this->pretest->questions->map(function ($q) {
                $attemptAnswer = $this->attemptAnswers[$q->id] ?? null;
                $correctOption = $q->options->first(fn($o) => $o->is_correct);
                
                return [
                    'id' => 'q' . $q->id,
                    'db_id' => $q->id,
                    'type' => $q->question_type,
                    'text' => $q->text,
                    'max_points' => $q->max_points ?? 1,
                    'options' => $q->question_type === 'multiple'
                        ? $q->options->map(fn($o) => [
                            'id' => $o->id,
                            'text' => $o->text,
                            'is_correct' => $o->is_correct,
                        ])->values()->all()
                        : [],
                    // Review data
                    'user_answer_id' => $attemptAnswer['selected_option_id'] ?? null,
                    'user_essay_answer' => $attemptAnswer['essay_answer'] ?? null,
                    'is_correct' => $attemptAnswer['is_correct'] ?? null,
                    'earned_points' => $attemptAnswer['earned_points'] ?? 0,
                    'correct_option_id' => $correctOption?->id,
                    'correct_option_text' => $correctOption?->text,
                ];
            });

            // Randomize once if flag set (only in non-review mode)
            if (!$this->isReviewMode && $this->pretest->randomize_question) {
                $collection = $collection->shuffle();
            }

            $this->questions = $collection->values()->all();
        }
    }

    public function render()
    {
        $userId = Auth::id();
        
        // Check if user has posttest attempt for sidebar navigation
        $posttest = Test::where('course_id', $this->course->id)->where('type', 'posttest')->select('id')->first();
        $hasPosttestAttempt = false;
        if ($posttest) {
            $hasPosttestAttempt = TestAttempt::where('test_id', $posttest->id)
                ->where('user_id', $userId)
                ->exists();
        }
        
        /** @var \Illuminate\View\View&\App\Support\Ide\LivewireViewMacros $view */
        $view = view('pages.courses.pretest', [
            'course' => $this->course,
            'pretest' => $this->pretest,
            'questions' => $this->questions,
            'pretestId' => $this->pretest?->id,
            'userId' => $userId,
            'isReviewMode' => $this->isReviewMode,
            'attempt' => $this->attempt,
        ]);

        return $view->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'pretest',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'hasPosttestAttempt' => $hasPosttestAttempt,
            'courseId' => $this->course->id,
        ]);
    }

    /**
     * Handle pretest submission: persist attempt and answers, auto-grade MC, update progress, redirect.
     */
    public function submitPretest(array $answers)
    {
        $t0 = microtime(true);
        $userId = Auth::id();
        if (!$userId) abort(401);
        if (!$this->pretest) abort(400, 'Pretest not configured.');

        // Verify enrollment
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) abort(403);

        // Prevent duplicate submissions
        $alreadySubmitted = TestAttempt::where('test_id', $this->pretest->id)
            ->where('user_id', $userId)
            ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
            ->exists();
        if ($alreadySubmitted) {
            return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
        }

        // Load questions fresh to validate and grade
        $t1 = microtime(true);
        $questionRows = TestQuestion::select('id', 'test_id', 'question_type', 'max_points')
            ->where('test_id', $this->pretest->id)
            ->get()
            ->keyBy('id');
        $t2 = microtime(true);

        if ($questionRows->isEmpty()) {
            abort(422, 'No questions configured.');
        }

        // Validate required answers exist for each question
        foreach ($questionRows as $qid => $q) {
            $key = 'q' . $qid;
            if (!array_key_exists($key, $answers) || ($answers[$key] === null || $answers[$key] === '')) {
                abort(422, 'Some answers are missing.');
            }
        }

        $attemptNumber = (int) ((TestAttempt::where('test_id', $this->pretest->id)->where('user_id', $userId)->max('attempt_number')) ?? 0) + 1;

        // Pre-fetch only selected options to minimize DB work
        $selectedOptionIds = [];
        foreach ($questionRows as $qid => $q) {
            if ($q->question_type === 'multiple') {
                $key = 'q' . $qid;
                if (isset($answers[$key])) {
                    $selectedOptionIds[] = (int) $answers[$key];
                }
            }
        }
        $optionsById = collect();
        if (!empty($selectedOptionIds)) {
            $optionsById = TestQuestionOption::select('id', 'question_id', 'is_correct')
                ->whereIn('id', $selectedOptionIds)
                ->get()
                ->keyBy('id');
        }
        $t3 = microtime(true);

        // Determine if any essay/open-ended questions exist (non-multiple)
        $hasEssay = $questionRows->contains(function ($q) {
            return ($q->question_type !== 'multiple');
        });

        DB::transaction(function () use ($userId, $answers, $questionRows, $attemptNumber, $enrollment, $optionsById, $t0, $t1, $t2, $t3, $hasEssay) {
            $now = now();
            $attempt = TestAttempt::create([
                'user_id' => $userId,
                'test_id' => $this->pretest->id,
                'attempt_number' => $attemptNumber,
                'status' => $hasEssay ? TestAttempt::STATUS_UNDER_REVIEW : TestAttempt::STATUS_SUBMITTED,
                'auto_score' => 0,
                'manual_score' => 0,
                'total_score' => 0,
                'is_passed' => false,
                'started_at' => $now,
                'submitted_at' => $now,
                'expired_at' => null,
            ]);

            $autoScore = 0;
            $inserts = [];
            foreach ($answers as $frontendKey => $value) {
                if (!str_starts_with($frontendKey, 'q')) continue;
                $qid = (int) substr($frontendKey, 1);
                if (!$qid || !isset($questionRows[$qid])) continue;
                $q = $questionRows[$qid];

                $selectedOptionId = null;
                $essayAnswer = null;
                $isCorrect = null;
                $earned = 0;

                if ($q->question_type === 'multiple') {
                    $selectedOptionId = (int) $value;
                    $opt = $optionsById->get($selectedOptionId);
                    if (!$opt || (int) $opt->question_id !== (int) $qid) abort(422, 'Invalid option.');
                    if ($opt->is_correct) {
                        $earned = (int) ($q->max_points ?? 1);
                        $isCorrect = true;
                    } else {
                        $earned = 0;
                        $isCorrect = false;
                    }
                    $autoScore += $earned;
                } else {
                    $essayAnswer = (string) $value;
                    $earned = 0; // manual grading later
                    $isCorrect = null;
                }

                $inserts[] = [
                    'attempt_id' => $attempt->id,
                    'question_id' => $qid,
                    'selected_option_id' => $selectedOptionId,
                    'essay_answer' => $essayAnswer,
                    'is_correct' => $isCorrect,
                    'earned_points' => $earned,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($inserts) {
                TestAttemptAnswer::insert($inserts);
            }

            $attempt->auto_score = $autoScore;
            $attempt->manual_score = 0;
            $attempt->total_score = $autoScore;
            // Determine pass only if not under review (i.e., no essay questions)
            if (!$hasEssay) {
                $passingScore = (int) ($this->pretest->passing_score ?? 0);
                if ($passingScore > 0) {
                    $maxAutoPoints = $questionRows->where('question_type', 'multiple')->sum('max_points');
                    $percent = $maxAutoPoints > 0 ? (int) round(($autoScore / max(1, $maxAutoPoints)) * 100) : 0;
                    $attempt->is_passed = $percent >= $passingScore;
                }
            }
            $attempt->save();

            // Update user progress: mark at least step 1 completed (pretest done)
            if (($enrollment->current_step ?? 0) < 1) {
                $enrollment->current_step = 1;
                $enrollment->save();
            }
            // Ensure status reflects engagement
            if (($enrollment->status ?? '') === '' || strtolower($enrollment->status) === 'not_started') {
                $enrollment->status = 'in_progress';
                $enrollment->save();
            }

            // Micro timing log (optional; visible in laravel.log)
            try {
                $t4 = microtime(true);
                Log::info('Pretest submit timings', [
                    'user_id' => $userId,
                    'test_id' => $this->pretest->id,
                    'phase' => [
                        'start_to_dupCheck' => $t1 - $t0,
                        'dupCheck_to_loadQuestions' => $t2 - $t1,
                        'loadQuestions_to_loadOptions' => $t3 - $t2,
                        'transaction_total' => $t4 - $t3,
                        'overall' => $t4 - $t0,
                    ],
                ]);
            } catch (\Throwable $e) {
            }
        });

        // Redirect to learning modules page (SPA navigate)
        return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
    }
}
