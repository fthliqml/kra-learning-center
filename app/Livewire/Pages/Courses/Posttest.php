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

        // Gate: only allow access to posttest after completing the course flow
        // Requirement: user must have finished all units except the posttest unit itself.
        if ($userId && $enrollment) {
            $totalUnits = (int) $this->course->progressUnitsCount(); // pretest + sections/topics + posttest
            $requiredStep = max(0, $totalUnits - 1); // all prior units
            $currentStep = (int) ($enrollment->current_step ?? 0);
            if ($currentStep < $requiredStep) {
                // Not yet eligible for posttest -> continue modules first
                return redirect()->route('courses-modules.index', ['course' => $course->id]);
            }
        }

        // Load posttest with questions and options
        $this->posttest = Test::with([
            'questions' => function ($q) {
                $q->select('id', 'test_id', 'question_type', 'text', 'max_points');
            },
            'questions.options' => function ($q) {
                $q->select('id', 'question_id', 'text');
            },
        ])
            ->where('course_id', $this->course->id)
            ->where('type', 'posttest')
            ->first();

        if ($this->posttest) {
            // If already submitted an attempt, go back to modules/result
            $alreadySubmitted = TestAttempt::where('test_id', $this->posttest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
                ->exists();
            if ($alreadySubmitted) {
                return redirect()->route('courses-modules.index', ['course' => $course->id]);
            }

            $collection = $this->posttest->questions->map(function ($q) {
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

            if ($this->posttest->randomize_question) {
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

        return view('pages.courses.posttest', [
            'course' => $this->course,
            'posttest' => $this->posttest,
            'questions' => $this->questions,
            'posttestId' => $this->posttest?->id,
            'userId' => $userId,
        ])->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'posttest',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'completedModuleIds' => $completedModuleIds,
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

        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) abort(403);

        $alreadySubmitted = TestAttempt::where('test_id', $this->posttest->id)
            ->where('user_id', $userId)
            ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
            ->exists();
        if ($alreadySubmitted) {
            return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
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

            // Mark step 3 (posttest) as completed at minimum
            if (($enrollment->current_step ?? 0) < 3) {
                $enrollment->current_step = 3;
                $enrollment->save();
            }
        });

        // If perfect score (100%), mark course progress as fully completed.
        $maxAutoPoints = $questionRows->where('question_type', 'multiple')->sum('max_points');
        $latestAuto = TestAttempt::where('test_id', $this->posttest->id)
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->value('auto_score');
        $percent = ($maxAutoPoints > 0) ? (int) round(((int) $latestAuto / max(1, $maxAutoPoints)) * 100) : 0;
        if ($percent === 100) {
            $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
            if ($enrollment) {
                $totalUnits = (int) $this->course->progressUnitsCount();
                $enrollment->current_step = $totalUnits;
                $enrollment->status = 'completed';
                $enrollment->save();
            }
        }

        // Redirect to results page
        return redirect()->route('courses-result.index', ['course' => $this->course->id]);
    }
}
