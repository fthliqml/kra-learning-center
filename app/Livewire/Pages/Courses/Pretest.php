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

    public bool $isRetakeFlow = false;
    
    // Stats mode: show result stats without revealing answers
    public bool $isStatsMode = false;
    public ?TestAttempt $lastAttempt = null;
    public bool $canRetake = false;
    public int $remainingAttempts = 0;

    public function mount(Course $course)
    {
        $userId = Auth::id();
        // Assigned via TrainingAssessment within the training schedule window
        $today = now()->startOfDay();
        $assigned = $course->trainings()
            ->whereHas('assessments', function ($a) use ($userId) {
                $a->where('employee_id', $userId);
            })
            ->exists();
        if (!$assigned) {
            abort(403, 'You are not assigned to this course.');
        }

        // Gate: outside schedule window, course content is locked.
        // If the user already passed, send them to Result (not Overview) for review.
        if ($userId && !$course->isAvailableForUser($userId)) {
            $postRow = Test::where('course_id', $course->id)->where('type', 'posttest')->select('id')->first();
            $hasPassedPosttest = false;
            if ($postRow) {
                $hasPassedPosttest = TestAttempt::where('test_id', $postRow->id)
                    ->where('user_id', $userId)
                    ->where('is_passed', true)
                    ->exists();
            }

            if ($hasPassedPosttest) {
                return redirect()->route('courses-result.index', ['course' => $course->id]);
            }

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

        // Eager load learning modules + sections for sidebar listing
        $this->course = $course->load(['learningModules' => function ($q) {
            $q->orderBy('id')->with(['sections' => function ($s) {
                $s->select('id', 'topic_id', 'title')->orderBy('id');
            }]);
        }]);

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
            $forceRetake = (string) request()->query('retake', '') === '1';

            // Remedial rule: if user is repeating the course because they failed the Post-Test,
            // do not show Pre-Test review (prevents memorizing correct answers). Force retake.
            $isRemedial = false;
            $posttest = Test::where('course_id', $course->id)
                ->where('type', 'posttest')
                ->select(['id'])
                ->first();
            if ($posttest) {
                $lastPosttestAttempt = TestAttempt::where('test_id', $posttest->id)
                    ->where('user_id', $userId)
                    ->orderByDesc('submitted_at')->orderByDesc('id')
                    ->first();
                if ($lastPosttestAttempt && !$lastPosttestAttempt->is_passed && $lastPosttestAttempt->status !== TestAttempt::STATUS_UNDER_REVIEW) {
                    $isRemedial = true;
                }
            }

            if ($isRemedial) {
                $forceRetake = true;
            }
            $this->isRetakeFlow = $forceRetake;

            $maxAttempts = null;
            if ($this->pretest->max_attempts !== null) {
                $maxAttempts = max(1, (int) $this->pretest->max_attempts);
            }

            // Attempts already submitted/under review
            $attemptCount = (int) TestAttempt::where('test_id', $this->pretest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [
                    TestAttempt::STATUS_SUBMITTED,
                    TestAttempt::STATUS_UNDER_REVIEW,
                    TestAttempt::STATUS_EXPIRED,
                ])
                ->count();

            $existingAttempt = null;
            if ($attemptCount > 0) {
                $existingAttempt = TestAttempt::where('test_id', $this->pretest->id)
                    ->where('user_id', $userId)
                    ->whereIn('status', [
                        TestAttempt::STATUS_SUBMITTED,
                        TestAttempt::STATUS_UNDER_REVIEW,
                        TestAttempt::STATUS_EXPIRED,
                    ])
                    ->orderByDesc('submitted_at')
                    ->first();
            }

            // Check if user has posttest attempt (remedial mode)
            $posttest = Test::where('course_id', $course->id)->where('type', 'posttest')->select('id')->first();
            $hasPosttestAttempt = false;
            $hasPassedPosttestAttempt = false;
            if ($posttest) {
                $hasPosttestAttempt = TestAttempt::where('test_id', $posttest->id)
                    ->where('user_id', $userId)
                    ->exists();

                $hasPassedPosttestAttempt = TestAttempt::where('test_id', $posttest->id)
                    ->where('user_id', $userId)
                    ->where('is_passed', true)
                    ->exists();
            }

            if ($existingAttempt) {
                // Check if force retake via query param
                $forceRetake = (string) request()->query('retake', '') === '1';
                
                // If course is completed (posttest passed), show stats mode (read only)
                if ($hasPassedPosttestAttempt) {
                    $this->isStatsMode = true;
                    $this->lastAttempt = $existingAttempt;
                    $this->canRetake = false; // Course completed, no more retakes
                    $this->remainingAttempts = 0;
                    return;
                }
                
                // Check if user passed pretest
                $hasPassed = TestAttempt::where('test_id', $this->pretest->id)
                    ->where('user_id', $userId)
                    ->where('is_passed', true)
                    ->exists();
                
                if ($hasPassed) {
                    // Passed - show stats mode (user can proceed next via button)
                    $this->isStatsMode = true;
                    $this->lastAttempt = $existingAttempt;
                    $this->canRetake = false; // Already passed, no need to retake
                    $this->remainingAttempts = 0;
                    return; // Render stats
                }
                
                // Not passed - calculate retake eligibility
                $canStillRetake = false;
                $remaining = 0;
                if ($maxAttempts === null) {
                    // Pretest should not be unlimited - treat as exhausted
                    $canStillRetake = false;
                    $remaining = 0;
                } elseif ($maxAttempts <= 1) {
                    // Single attempt only
                    $canStillRetake = false;
                    $remaining = 0;
                } else {
                    // Multiple attempts allowed
                    $canStillRetake = $attemptCount < $maxAttempts;
                    $remaining = max(0, $maxAttempts - $attemptCount);
                }
                
                // If force retake AND can retake, show form
                if ($forceRetake && $canStillRetake) {
                    // Continue to form mode below
                } else {
                    // Show stats mode
                    $this->isStatsMode = true;
                    $this->lastAttempt = $existingAttempt;
                    $this->canRetake = $canStillRetake;
                    $this->remainingAttempts = $remaining;
                    
                    return; // Don't load questions for stats mode
                }
            }

            $collection = $this->pretest->questions->map(function ($q) {
                // Form mode only: do NOT include correctness fields (avoid leaking answers)
                return [
                    'id' => 'q' . $q->id,
                    'db_id' => $q->id,
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
            if ($this->pretest->randomize_question) {
                $collection = $collection->shuffle();
            }

            $this->questions = $collection->values()->all();
        }
    }

    /**
     * Start retake - redirect to pretest with retake flag
     */
    public function startRetake(): mixed
    {
        return redirect()->route('courses-pretest.index', [
            'course' => $this->course->id,
            'retake' => 1,
        ]);
    }

    public function render()
    {
        $userId = Auth::id();

        // Sidebar completion indicators (match Module/Posttest behavior)
        $enrollment = $this->course->userCourses()
            ->where('user_id', $userId)
            ->select(['id', 'user_id', 'course_id', 'current_step'])
            ->first();
        $currentStep = (int) ($enrollment->current_step ?? 0);

        $orderedSectionRefs = [];
        foreach ($this->course->learningModules as $topic) {
            foreach (($topic->sections ?? collect()) as $section) {
                $orderedSectionRefs[] = $section;
            }
        }
        $sectionsTotal = count($orderedSectionRefs);
        $hasPretest = Test::where('course_id', $this->course->id)->where('type', 'pretest')->exists();
        $preUnits = $hasPretest ? 1 : 0;
        $completedCount = max(0, min($currentStep - $preUnits, $sectionsTotal));
        for ($i = 0; $i < $completedCount; $i++) {
            if (isset($orderedSectionRefs[$i])) {
                $orderedSectionRefs[$i]->is_completed = true;
            }
        }

        $completedModuleIds = [];
        foreach ($this->course->learningModules as $topic) {
            $secs = $topic->sections ?? collect();
            $count = $secs->count();
            if ($count === 0) continue;
            $doneInTopic = $secs->filter(fn($s) => !empty($s->is_completed))->count();
            if ($doneInTopic > 0 && $doneInTopic === $count) {
                $completedModuleIds[] = $topic->id;
            }
        }

        // Check if user has posttest attempt for sidebar navigation
        $posttest = Test::where('course_id', $this->course->id)
            ->where('type', 'posttest')
            ->select(['id', 'max_attempts'])
            ->first();
        $hasPosttestAttempt = false;
        $canRetakePosttest = false;
        $hasPassedPosttest = false;
        if ($posttest) {
            $lastAttempt = TestAttempt::where('test_id', $posttest->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            if ($lastAttempt) {
                $hasPosttestAttempt = true;
                $hasPassedPosttest = (bool) $lastAttempt->is_passed;
                if (!$lastAttempt->is_passed && $lastAttempt->status !== TestAttempt::STATUS_UNDER_REVIEW) {
                    $attemptCount = (int) TestAttempt::where('test_id', $posttest->id)
                        ->where('user_id', $userId)
                        ->whereIn('status', [
                            TestAttempt::STATUS_SUBMITTED,
                            TestAttempt::STATUS_UNDER_REVIEW,
                            TestAttempt::STATUS_EXPIRED,
                        ])
                        ->count();
                    if ($posttest->max_attempts === null) {
                        $canRetakePosttest = true;
                    } else {
                        $maxAttempts = max(1, (int) $posttest->max_attempts);
                        $canRetakePosttest = $attemptCount < $maxAttempts;
                    }
                }
            }
        }

        $canRetakePretest = false;
        if ($this->pretest) {
            if ($this->pretest->max_attempts === null) {
                $canRetakePretest = true;
            } else {
                $maxAttempts = max(1, (int) $this->pretest->max_attempts);
                $canRetakePretest = $maxAttempts > 1;
            }
        }

        /** @var \Illuminate\View\View&\App\Support\Ide\LivewireViewMacros $view */
        $view = view('pages.courses.pretest', [
            'course' => $this->course,
            'pretest' => $this->pretest,
            'questions' => $this->questions,
            'pretestId' => $this->pretest?->id,
            'userId' => $userId,
            'isStatsMode' => $this->isStatsMode,
            'lastAttempt' => $this->lastAttempt,
            'canRetake' => $this->canRetake,
            'remainingAttempts' => $this->remainingAttempts,
        ]);

        return $view->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'pretest',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'completedModuleIds' => $completedModuleIds,
            'hasPosttestAttempt' => $hasPosttestAttempt,
            'canRetakePosttest' => $canRetakePosttest,
            'canRetakePretest' => $canRetakePretest,
            'hasPassedPosttest' => $hasPassedPosttest,
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

        // Gate: prevent submit outside schedule window
        if (!$this->course->isAvailableForUser($userId)) {
            $postRow = Test::where('course_id', $this->course->id)->where('type', 'posttest')->select('id')->first();
            $hasPassedPosttest = false;
            if ($postRow) {
                $hasPassedPosttest = TestAttempt::where('test_id', $postRow->id)
                    ->where('user_id', $userId)
                    ->where('is_passed', true)
                    ->exists();
            }
            if ($hasPassedPosttest) {
                return redirect()->route('courses-result.index', ['course' => $this->course->id]);
            }
            return redirect()->route('courses-overview.show', ['course' => $this->course->id]);
        }

        // Verify enrollment
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) abort(403);

        // If user is already past the pretest step, this submission is a retake action.
        $isRetakeContext = ((int) ($enrollment->current_step ?? 0)) > 1;
        if ($this->isRetakeFlow) {
            $isRetakeContext = true;
        }

        // Remedial mode detection: failed last posttest attempt.
        $isRemedial = false;
        $posttest = Test::where('course_id', $this->course->id)
            ->where('type', 'posttest')
            ->select('id')
            ->first();
        if ($posttest) {
            $lastPosttestAttempt = TestAttempt::where('test_id', $posttest->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            if ($lastPosttestAttempt) {
                $isRetakeContext = true;
                if (!$lastPosttestAttempt->is_passed && $lastPosttestAttempt->status !== TestAttempt::STATUS_UNDER_REVIEW) {
                    $isRemedial = true;
                }
            }
        }

        $attemptCount = (int) TestAttempt::where('test_id', $this->pretest->id)
            ->where('user_id', $userId)
            ->whereIn('status', [
                TestAttempt::STATUS_SUBMITTED,
                TestAttempt::STATUS_UNDER_REVIEW,
                TestAttempt::STATUS_EXPIRED,
            ])
            ->count();

        if ($this->pretest->max_attempts !== null) {
            $maxAttempts = max(1, (int) $this->pretest->max_attempts);
            if ($attemptCount >= $maxAttempts) {
                return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
            }
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

        // Check the latest attempt result to determine redirect
        $latestAttempt = TestAttempt::where('test_id', $this->pretest->id)
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->first();
        
        $isPassed = $latestAttempt?->is_passed ?? false;
        
        // If not passed, check if user can still retake
        if (!$isPassed) {
            $attemptCount = (int) TestAttempt::where('test_id', $this->pretest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [
                    TestAttempt::STATUS_SUBMITTED,
                    TestAttempt::STATUS_UNDER_REVIEW,
                    TestAttempt::STATUS_EXPIRED,
                ])
                ->count();
            
            $maxAttempts = $this->pretest->max_attempts;
            $canStillRetake = false;
            
            if ($maxAttempts === null) {
                // Pretest should not be unlimited - treat as exhausted
                $canStillRetake = false;
            } else {
                $maxAttempts = max(1, (int) $maxAttempts);
                $canStillRetake = $attemptCount < $maxAttempts;
            }
            
            if ($canStillRetake) {
                // Failed but can retake - redirect to pretest to try again
                session()->flash('pretest_failed', 'Anda belum lulus Pre-Test. Silakan coba lagi. Sisa kesempatan: ' . ($maxAttempts - $attemptCount));
                return redirect()->route('courses-pretest.index', ['course' => $this->course->id]);
            }
            // Attempts exhausted - proceed to modules even though failed
        }

        // Always redirect back to Pretest page to show stats (Passed/Failed)
        // From there, user can click "Lanjut ke Materi"
        return redirect()->route('courses-pretest.index', ['course' => $this->course->id]);
    }
}
