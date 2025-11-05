<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestAttemptAnswer;
use App\Models\TestQuestion;
use App\Models\SectionQuizAttempt;
use App\Models\Section;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Result extends Component
{
    public Course $course;

    public ?array $pretest = null;   // ['attempt' => TestAttempt|null, 'percent' => int|null, 'max' => int]
    public ?array $posttest = null;  // ['attempt' => TestAttempt|null, 'percent' => int|null, 'max' => int, 'passing' => int|null]
    public array $quizzes = [];      // list of per-section quiz results

    public function mount(Course $course)
    {
        $userId = Auth::id();
        if (!$userId) abort(401);

        // Ensure user is assigned to this course
        $assigned = $course->trainings()
            ->whereHas('assessments', function ($a) use ($userId) {
                $a->where('employee_id', $userId);
            })
            ->exists();
        if (!$assigned) {
            abort(403);
        }

        // Load modules/sections for sidebar
        $this->course = $course->load(['learningModules' => function ($q) {
            $q->orderBy('id')->with(['sections' => function ($s) {
                $s->select('id', 'topic_id', 'title')->orderBy('id');
            }]);
        }]);

        // Pretest summary
        $pre = Test::where('course_id', $course->id)->where('type', 'pretest')->first();
        if ($pre) {
            $attempt = TestAttempt::where('test_id', $pre->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            $maxAuto = (int) TestQuestion::where('test_id', $pre->id)
                ->where('question_type', 'multiple')
                ->sum('max_points');
            if ($maxAuto === 0) {
                $maxAuto = (int) TestQuestion::where('test_id', $pre->id)
                    ->where('question_type', 'multiple')
                    ->count();
            }
            $percent = null;
            if ($attempt && $maxAuto > 0) {
                $percent = (int) round(($attempt->auto_score / max(1, $maxAuto)) * 100);
            }
            // Extra aggregates for donut
            $mcTotalPre = (int) TestQuestion::where('test_id', $pre->id)
                ->where('question_type', 'multiple')
                ->count();
            $qTotalPre = (int) TestQuestion::where('test_id', $pre->id)->count();
            $correctPre = 0;
            if ($attempt) {
                $correctPre = (int) TestAttemptAnswer::where('attempt_id', $attempt->id)
                    ->where('is_correct', true)
                    ->count();
            }
            $this->pretest = [
                'test' => $pre,
                'attempt' => $attempt,
                'max_auto' => $maxAuto,
                'percent' => $percent,
                'mc_total' => $mcTotalPre,
                'q_total' => $qTotalPre,
                'correct' => $correctPre,
            ];
        }

        // Posttest summary
        $post = Test::where('course_id', $course->id)->where('type', 'posttest')->first();
        if ($post) {
            $attempt = TestAttempt::where('test_id', $post->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            $maxAuto = (int) TestQuestion::where('test_id', $post->id)
                ->where('question_type', 'multiple')
                ->sum('max_points');
            if ($maxAuto === 0) {
                $maxAuto = (int) TestQuestion::where('test_id', $post->id)
                    ->where('question_type', 'multiple')
                    ->count();
            }
            $percent = null;
            if ($attempt && $maxAuto > 0) {
                $percent = (int) round(($attempt->auto_score / max(1, $maxAuto)) * 100);
            }
            // Extra aggregates for donut chart and history
            $mcTotal = (int) TestQuestion::where('test_id', $post->id)
                ->where('question_type', 'multiple')
                ->count();
            $qTotal = (int) TestQuestion::where('test_id', $post->id)->count();
            $correct = 0;
            if ($attempt) {
                $correct = (int) TestAttemptAnswer::where('attempt_id', $attempt->id)
                    ->where('is_correct', true)
                    ->count();
            }

            // Attempt history (latest first)
            $attemptRows = TestAttempt::where('test_id', $post->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->get();
            $passingScore = (int) ($post->passing_score ?? 0);
            $attempts = $attemptRows->map(function ($a) use ($maxAuto, $passingScore) {
                $pct = ($maxAuto > 0) ? (int) round(($a->auto_score / max(1, $maxAuto)) * 100) : null;
                $isUnderReview = ($a->status === TestAttempt::STATUS_UNDER_REVIEW);
                $derivedPass = false;
                if (!$isUnderReview && $pct !== null) {
                    if ($pct === 100) {
                        $derivedPass = true;
                    } elseif ($passingScore > 0 && $pct >= $passingScore) {
                        $derivedPass = true;
                    } elseif ($a->is_passed) {
                        $derivedPass = true;
                    }
                }
                return [
                    'number' => (int) $a->attempt_number,
                    'submitted_at' => optional($a->submitted_at)->format('Y-m-d H:i') ?? '-',
                    'auto' => (int) $a->auto_score,
                    'total' => (int) $a->total_score,
                    'percent' => $pct,
                    'status' => (string) $a->status,
                    'passed' => $derivedPass,
                ];
            })->values()->all();

            $this->posttest = [
                'test' => $post,
                'attempt' => $attempt,
                'max_auto' => $maxAuto,
                'percent' => $percent,
                'passing' => $post->passing_score,
                'mc_total' => $mcTotal,
                'q_total' => $qTotal,
                'correct' => $correct,
                'attempts' => $attempts,
            ];
        }

        // Per-section quiz results for this course
        // Collect all section IDs that belong to this course
        $sectionIds = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $sec) {
                $sectionIds[] = (int) $sec->id;
            }
        }
        if (!empty($sectionIds)) {
            $rows = SectionQuizAttempt::with(['section' => function ($q) {
                $q->select('id', 'topic_id', 'title')->with(['topic' => function ($t) {
                    $t->select('id', 'title');
                }]);
            }])
                ->where('user_id', $userId)
                ->whereIn('section_id', $sectionIds)
                ->orderByDesc('completed_at')
                ->orderByDesc('id')
                ->get();

            $this->quizzes = $rows->map(function ($r) {
                return [
                    'section_id' => (int) $r->section_id,
                    'module' => (string) ($r->section?->topic?->title ?? ''),
                    'section' => (string) ($r->section?->title ?? ''),
                    'score' => (int) ($r->score ?? 0),
                    'total' => (int) ($r->total_questions ?? 0),
                    'passed' => (bool) ($r->passed ?? false),
                    'completed_at' => optional($r->completed_at)->format('Y-m-d H:i') ?? '-',
                ];
            })->values()->all();
        }
    }

    public function render()
    {
        // Compute completed modules for sidebar checkmarks
        $userId = Auth::id();
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->select(['id', 'user_id', 'course_id', 'current_step'])->first();
        $currentStep = (int) ($enrollment->current_step ?? 0);

        $orderedSectionRefs = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $section) {
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

        return view('pages.courses.result', [
            'course' => $this->course,
            'pre' => $this->pretest,
            'post' => $this->posttest,
            'quizzes' => $this->quizzes,
        ])->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'result',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'completedModuleIds' => $completedModuleIds,
        ]);
    }
}
