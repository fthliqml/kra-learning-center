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

class Posttest extends Component
{
    public Course $course;
    public ?Test $posttest = null;
    public array $questions = [];
    public bool $isReviewMode = false;
    public ?TestAttempt $attempt = null;
    /** @var array<int, array{selected_option_id?: int|null, essay_answer?: string|null, is_correct?: bool|null, earned_points?: int|null}> */
    protected array $attemptAnswers = [];

    public function mount(Course $course)
    {
        $userId = Auth::id();
        // Must be assigned to this course via training assessment
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

        // Ensure enrollment exists
        $enrollment = null;
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

        $this->course = $course->load(['learningModules' => fn($q) => $q->orderBy('id')]);
        $this->course = $course->load(['learningModules' => function ($q) {
            $q->orderBy('id')->with(['sections' => function ($s) {
                $s->select('id', 'topic_id', 'title')->orderBy('id');
            }]);
        }]);

        // Gate: allow access after finishing modules, OR if last posttest attempt failed (remedial)
        if ($userId && $enrollment) {
            $allowRemedial = false;
            $postRow = Test::where('course_id', $course->id)->where('type', 'posttest')->select('id')->first();
            if ($postRow) {
                $lastAttempt = TestAttempt::where('test_id', $postRow->id)
                    ->where('user_id', $userId)
                    ->orderByDesc('submitted_at')->orderByDesc('id')
                    ->first();
                if ($lastAttempt && !$lastAttempt->is_passed) {
                    $allowRemedial = true;
                }
            }

            if (!$allowRemedial) {
                $totalUnits = (int) $this->course->progressUnitsCount(); // pretest + sections/topics + posttest
                $requiredStep = max(0, $totalUnits - 1); // all prior units
                $currentStep = (int) ($enrollment->current_step ?? 0);
                if ($currentStep < $requiredStep) {
                    // Not yet eligible for posttest -> continue modules first
                    return redirect()->route('courses-modules.index', ['course' => $course->id]);
                }
            }
        }

        // Load posttest with questions and options
        $this->posttest = Test::with([
            'questions' => function ($q) {
                $q->select('id', 'test_id', 'question_type', 'text', 'max_points');
            },
            'questions.options' => function ($q) {
                $q->select('id', 'question_id', 'text', 'is_correct');
            },
        ])
            ->where('course_id', $this->course->id)
            ->where('type', 'posttest')
            ->first();

        if ($this->posttest) {
            // Determine latest attempt (for review mode after passing)
            $latestAttempt = TestAttempt::where('test_id', $this->posttest->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();

            // Under review: do not reveal answers here (go to result)
            if ($latestAttempt && $latestAttempt->status === TestAttempt::STATUS_UNDER_REVIEW) {
                return redirect()->route('courses-result.index', ['course' => $course->id]);
            }

            // Passed: enter review mode (read-only)
            if ($latestAttempt && $latestAttempt->is_passed) {
                $this->isReviewMode = true;
                $this->attempt = $latestAttempt;

                $this->attemptAnswers = TestAttemptAnswer::query()
                    ->where('attempt_id', $latestAttempt->id)
                    ->select(['question_id', 'selected_option_id', 'essay_answer', 'is_correct', 'earned_points'])
                    ->get()
                    ->keyBy('question_id')
                    ->map(fn($a) => [
                        'selected_option_id' => $a->selected_option_id,
                        'essay_answer' => $a->essay_answer,
                        'is_correct' => $a->is_correct,
                        'earned_points' => $a->earned_points,
                    ])
                    ->toArray();
            }

            $collection = $this->posttest->questions->map(function ($q) {
                if ($this->isReviewMode) {
                    $attemptAnswer = $this->attemptAnswers[$q->id] ?? null;
                    $correctOption = $q->options->first(fn($o) => $o->is_correct);

                    $userSelectedOptionId = null;
                    $userEssayAnswer = null;
                    $isCorrect = null;
                    $earnedPoints = 0;

                    if (is_array($attemptAnswer)) {
                        $userSelectedOptionId = $attemptAnswer['selected_option_id'] ?? null;
                        $userEssayAnswer = $attemptAnswer['essay_answer'] ?? null;
                        $isCorrect = $attemptAnswer['is_correct'] ?? null;
                        $earnedPoints = (int) ($attemptAnswer['earned_points'] ?? 0);

                        // Backward compatibility: some older attempts stored selected option in essay_answer
                        if ($q->question_type === 'multiple' && !$userSelectedOptionId && $userEssayAnswer !== null) {
                            $raw = trim((string) $userEssayAnswer);
                            if ($raw !== '' && ctype_digit($raw)) {
                                $userSelectedOptionId = (int) $raw;
                            }
                        }

                        // Derive correctness if missing for multiple-choice
                        if ($q->question_type === 'multiple' && $userSelectedOptionId && $isCorrect === null) {
                            $selected = $q->options->first(fn($o) => (string) $o->id === (string) $userSelectedOptionId);
                            if ($selected) {
                                $isCorrect = (bool) $selected->is_correct;
                                $earnedPoints = $isCorrect ? (int) ($q->max_points ?? 1) : 0;
                            }
                        }
                    }

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
                        'user_answer_id' => $userSelectedOptionId,
                        'user_essay_answer' => $userEssayAnswer,
                        'is_correct' => $isCorrect,
                        'earned_points' => $earnedPoints,
                        'correct_option_id' => $correctOption?->id,
                        'correct_option_text' => $correctOption?->text,
                    ];
                }

                // Form mode: do NOT include correctness fields (avoid leaking answers)
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

            // Randomize only in form mode
            if (!$this->isReviewMode && $this->posttest->randomize_question) {
                $collection = $collection->shuffle();
            }

            $this->questions = $collection->values()->all();
        }
    }

    public function render()
    {
        // Compute completed modules/sections for sidebar indicators
        $userId = Auth::id();
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->select(['id', 'user_id', 'course_id', 'current_step'])->first();
        $currentStep = (int) ($enrollment->current_step ?? 0);

        $orderedSectionRefs = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $section) {
                $orderedSectionRefs[] = $section; // references to mark is_completed
            }
        }
        $sectionsTotal = count($orderedSectionRefs);
        $hasPretest = Test::where('course_id', $this->course->id)->where('type', 'pretest')->exists();
        $pretestUnits = $hasPretest ? 1 : 0;
        $completedCount = max(0, min($currentStep - $pretestUnits, $sectionsTotal));

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

        // Build flat list of sections for dropdown navigation from Posttest
        $sectionsList = [];
        $lastSectionId = null;
        foreach ($this->course->learningModules as $topic) {
            $secs = $topic->sections ?? collect();
            foreach ($secs as $sec) {
                $sectionsList[] = [
                    'id' => (int) $sec->id,
                    'title' => (string) ($sec->title ?? 'Untitled'),
                    'module' => (string) ($topic->title ?? 'Module'),
                ];
                $lastSectionId = (int) $sec->id;
            }
        }

        // Show material picker only if user has attempted posttest and did not pass (exclude under review)
        $showMaterialPicker = false;
        if ($this->posttest) {
            $latest = TestAttempt::where('test_id', $this->posttest->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            if ($latest && $latest->status !== TestAttempt::STATUS_UNDER_REVIEW && !$latest->is_passed) {
                $showMaterialPicker = true;
            }
        }

        $hasPosttestAttempt = false;
        $canRetakePosttest = false;
        $hasPassedPosttest = false;
        if ($this->posttest) {
            $latest = TestAttempt::where('test_id', $this->posttest->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            if ($latest) {
                $hasPosttestAttempt = true;
                $hasPassedPosttest = (bool) $latest->is_passed;
                if (!$latest->is_passed && $latest->status !== TestAttempt::STATUS_UNDER_REVIEW) {
                    $attemptCount = (int) TestAttempt::where('test_id', $this->posttest->id)
                        ->where('user_id', $userId)
                        ->whereIn('status', [
                            TestAttempt::STATUS_SUBMITTED,
                            TestAttempt::STATUS_UNDER_REVIEW,
                            TestAttempt::STATUS_EXPIRED,
                        ])
                        ->count();

                    if ($this->posttest->max_attempts === null) {
                        $canRetakePosttest = true;
                    } else {
                        $maxAttempts = max(1, (int) $this->posttest->max_attempts);
                        $canRetakePosttest = $attemptCount < $maxAttempts;
                    }
                }
            }
        }

        $canRetakePretest = false;
        $pretestRow = Test::where('course_id', $this->course->id)
            ->where('type', 'pretest')
            ->select(['id', 'max_attempts'])
            ->first();
        if ($pretestRow) {
            if ($pretestRow->max_attempts === null) {
                $canRetakePretest = true;
            } else {
                $maxAttempts = max(1, (int) $pretestRow->max_attempts);
                $canRetakePretest = $maxAttempts > 1;
            }
        }

        /** @var \Illuminate\View\View&\App\Support\Ide\LivewireViewMacros $view */
        $view = view('pages.courses.posttest', [
            'course' => $this->course,
            'posttest' => $this->posttest,
            'questions' => $this->questions,
            'posttestId' => $this->posttest?->id,
            'userId' => $userId,
            'isReviewMode' => $this->isReviewMode,
            'attempt' => $this->attempt,
            'sectionsList' => $sectionsList,
            'showMaterialPicker' => $showMaterialPicker,
            'lastSectionId' => $lastSectionId,
        ]);

        return $view->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'posttest',
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
     * Handle posttest submission: same as pretest, but for type posttest.
     */
    public function submitPosttest(array $answers)
    {
        $t0 = microtime(true);
        $userId = Auth::id();
        if (!$userId) abort(401);
        if (!$this->posttest) abort(400, 'Posttest not configured.');

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

        // If already passed, do not allow submitting again from this page (review-only)
        $latestAttempt = TestAttempt::where('test_id', $this->posttest->id)
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->first();
        if ($latestAttempt && $latestAttempt->is_passed) {
            return redirect()->route('courses-result.index', ['course' => $this->course->id]);
        }

        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) abort(403);

        // Respect max_attempts if configured (NULL = unlimited)
        if ($this->posttest->max_attempts !== null) {
            $maxAttempts = max(1, (int) $this->posttest->max_attempts);
            $attemptCount = (int) TestAttempt::where('test_id', $this->posttest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [
                    TestAttempt::STATUS_SUBMITTED,
                    TestAttempt::STATUS_UNDER_REVIEW,
                    TestAttempt::STATUS_EXPIRED,
                ])
                ->count();
            if ($attemptCount >= $maxAttempts) {
                return redirect()->route('courses-result.index', ['course' => $this->course->id]);
            }
        }

        $t1 = microtime(true);
        $questionRows = TestQuestion::select('id', 'test_id', 'question_type', 'max_points')
            ->where('test_id', $this->posttest->id)
            ->get()
            ->keyBy('id');
        $t2 = microtime(true);

        if ($questionRows->isEmpty()) abort(422, 'No questions configured.');

        foreach ($questionRows as $qid => $q) {
            $key = 'q' . $qid;
            if (!array_key_exists($key, $answers) || ($answers[$key] === null || $answers[$key] === '')) {
                abort(422, 'Some answers are missing.');
            }
        }

        $attemptNumber = (int) ((TestAttempt::where('test_id', $this->posttest->id)->where('user_id', $userId)->max('attempt_number')) ?? 0) + 1;

        $selectedOptionIds = [];
        foreach ($questionRows as $qid => $q) {
            if ($q->question_type === 'multiple') {
                $key = 'q' . $qid;
                if (isset($answers[$key])) $selectedOptionIds[] = (int) $answers[$key];
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

        $hasEssay = $questionRows->contains(fn($q) => $q->question_type !== 'multiple');

        DB::transaction(function () use ($userId, $answers, $questionRows, $attemptNumber, $enrollment, $optionsById, $t0, $t1, $t2, $t3, $hasEssay) {
            $now = now();
            $attempt = TestAttempt::create([
                'user_id' => $userId,
                'test_id' => $this->posttest->id,
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
                    $earned = 0; // manual grading
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

            if ($inserts) TestAttemptAnswer::insert($inserts);

            $attempt->auto_score = $autoScore;
            $attempt->manual_score = 0;
            $attempt->total_score = $autoScore;

            if (!$hasEssay) {
                $passingScore = (int) ($this->posttest->passing_score ?? 0);
                if ($passingScore > 0) {
                    $maxAutoPoints = $questionRows->where('question_type', 'multiple')->sum('max_points');
                    $percent = $maxAutoPoints > 0 ? (int) round(($autoScore / max(1, $maxAutoPoints)) * 100) : 0;
                    $attempt->is_passed = $percent >= $passingScore;
                }
            }
            $attempt->save();

            // After posttest submission, update progress based on result
        });

        // Get the latest attempt to determine status
        $lastAttempt = TestAttempt::where('test_id', $this->posttest->id)
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->first();

        // Update progress based on attempt status
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if ($enrollment && $lastAttempt) {
            $totalUnits = (int) $this->course->progressUnitsCount();
            $current = (int) ($enrollment->current_step ?? 0);
            $status = $lastAttempt->status ?? '';
            $isPassed = $lastAttempt->is_passed ?? false;

            if ($status === TestAttempt::STATUS_SUBMITTED && $isPassed) {
                // PASSED: Mark as fully completed (100%)
                $enrollment->current_step = $totalUnits;
                $enrollment->status = 'completed';
            } else {
                // UNDER REVIEW or FAILED: Set to posttest-attempted level (totalUnits - 1)
                // This shows ~95% progress but user can still access result page
                $posttestAttemptedStep = max(0, $totalUnits - 1);
                if ($current < $posttestAttemptedStep) {
                    $enrollment->current_step = $posttestAttemptedStep;
                }
                // Ensure status is in_progress (not completed) for failed/under_review
                if (strtolower($enrollment->status ?? '') === 'completed') {
                    $enrollment->status = 'in_progress';
                }
            }

            $enrollment->save();
        }

        // Redirect to results page
        return redirect()->route('courses-result.index', ['course' => $this->course->id]);
    }
}
