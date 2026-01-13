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

        $attemptId = request()->query('attemptId');

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
            $query = TestAttempt::where('test_id', $post->id)
                ->where('user_id', $userId);

            if ($attemptId) {
                $attempt = $query->find($attemptId);
                // Ensure attempt belongs to user/test
                if (!$attempt || (int)$attempt->test_id !== (int)$post->id || (int)$attempt->user_id !== (int)$userId) {
                    abort(404);
                }
            } else {
                $attempt = $query->orderByDesc('submitted_at')->orderByDesc('id')->first();
            }

            $maxAuto = (int) TestQuestion::where('test_id', $post->id)
                ->where('question_type', 'multiple')
                ->sum('max_points');
            $maxManual = (int) TestQuestion::where('test_id', $post->id)
                ->whereIn('question_type', ['essay', 'text']) // assuming 'essay' or 'text'
                ->sum('max_points');
            $essayCount = (int) TestQuestion::where('test_id', $post->id)
                ->whereIn('question_type', ['essay', 'text'])
                ->count();

            if ($maxAuto === 0 && $maxManual === 0) {
                // Fallback if no points assigned
                $maxAuto = (int) TestQuestion::where('test_id', $post->id)->where('question_type', 'multiple')->count();
                $maxManual = $essayCount;
            }

            $percent = null;
            $maxTotal = $maxAuto + $maxManual;
            if ($attempt && $maxTotal > 0) {
                // If under review, percent is provisional (based on what's graded + auto)
                // But typically specific score logic happens in render
                $currentTotal = $attempt->auto_score + $attempt->manual_score;
                $percent = (int) round(($currentTotal / $maxTotal) * 100);
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

            // Attempt history (oldest → newest) to match attempt numbering
            $attemptRows = TestAttempt::where('test_id', $post->id)
                ->where('user_id', $userId)
                ->orderBy('attempt_number')
                ->orderBy('submitted_at')
                ->orderBy('id')
                ->get();
            $passingScore = (int) ($post->passing_score ?? 0);
            $currentAttemptId = $attempt?->id;
            $attempts = $attemptRows->map(function ($a) use ($maxAuto, $passingScore, $currentAttemptId) {
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
                    'id' => $a->id,
                    'number' => (int) $a->attempt_number,
                    'submitted_at' => optional($a->submitted_at)->format('Y-m-d H:i') ?? '-',
                    'auto' => (int) $a->auto_score,
                    'total' => (int) $a->total_score,
                    'percent' => $pct,
                    'status' => (string) $a->status,
                    'passed' => $derivedPass,
                    // Marks the attempt currently being viewed (query param attemptId or latest by default)
                    'is_current' => ($currentAttemptId !== null && (int) $a->id === (int) $currentAttemptId),
                ];
            })->values()->all();

            // Latest attempt globally
            $latestGlobal = TestAttempt::where('test_id', $post->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();

            $this->posttest = [
                'test' => $post,
                'attempt' => $attempt,
                'max_auto' => $maxAuto,
                'max_manual' => $maxManual,
                'essay_count' => $essayCount,
                'percent' => $percent,
                'passing' => $post->passing_score,
                'mc_total' => $mcTotal,
                'q_total' => $qTotal,
                'correct' => $correct,
                'attempts' => $attempts,
                'is_latest' => ($attempt && $latestGlobal && $attempt->id === $latestGlobal->id),
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

        // --- Logic moved from Blade to Controller ---
        $pre = $this->pretest ?? [];
        $post = $this->posttest ?? [];

        // Get attempt details
        $topAttempt = $post['attempt'] ?? null;
        $topUnderReview = $topAttempt && $topAttempt->status === \App\Models\TestAttempt::STATUS_UNDER_REVIEW;

        // Check if viewing historical attempt (not the latest)
        $isLatest = (bool) ($post['is_latest'] ?? true);
        $isHistorical = !$isLatest;

        // Calculate MC-only percentage (for Pilihan Ganda section)
        $maxAuto = (int) ($post['max_auto'] ?? 0);
        $mcScore = (int) ($topAttempt->auto_score ?? 0);
        $mcPct = $maxAuto > 0 ? (int) round(($mcScore / $maxAuto) * 100) : 0;

        // Calculate total percentage (MC + Essay)
        $maxManual = (int) ($post['max_manual'] ?? 0);
        $essayScore = (int) ($topAttempt->manual_score ?? 0);
        $maxTotal = $maxAuto + $maxManual;
        $totalScore = $mcScore + $essayScore;
        $totalPct = $maxTotal > 0 ? (int) round(($totalScore / $maxTotal) * 100) : 0;

        // Pre-test percentage
        $prePct = (int) ($pre['percent'] ?? 0 ?: 0);

        // For comparison chart: use MC-only if essay pending, else total
        $postPct = $topUnderReview ? $mcPct : $totalPct;
        $delta = $postPct - $prePct;

        // Determine pass/fail status
        $topPassed = false;
        if ($topAttempt && !$topUnderReview) {
            $passing = (int) ($post['passing'] ?? 0);
            if ($totalPct === 100) {
                $topPassed = true;
            } elseif ($passing > 0 && $totalPct >= $passing) {
                $topPassed = true;
            } elseif ((bool) $topAttempt->is_passed) {
                $topPassed = true;
            }
        }

        // Status label/color for summary card
        $statusLabel = 'Not Attempted';
        $statusColor = 'gray';
        if ($topAttempt) {
            if ($topUnderReview) {
                $statusLabel = 'Under Review';
                $statusColor = 'amber';
            } elseif ($topPassed) {
                $statusLabel = 'Passed';
                $statusColor = 'green';
            } else {
                $statusLabel = 'Failed';
                $statusColor = 'red';
            }
        }
        $statusIcon = match ($statusColor) {
            'green' => 'o-check-circle',
            'red' => 'o-x-circle',
            'amber' => 'o-clock',
            default => 'o-information-circle',
        };

        // Post-Test MC details
        $mcTotal = (int) ($post['mc_total'] ?? 0);
        $correct = (int) ($post['correct'] ?? 0);
        $incorrect = max(0, $mcTotal - $correct);
        $attempts = $post['attempts'] ?? [];
        $currentAttemptNum = (int) ($topAttempt->attempt_number ?? 0);

        // Essay details
        $essayCount = (int) ($post['essay_count'] ?? 0);
        $essayPending = $topUnderReview && $essayCount > 0;

        // Pre-Test details (for donut)
        $preMcTotal = (int) ($pre['mc_total'] ?? 0);
        $preCorrect = (int) ($pre['correct'] ?? 0);
        $preIncorrect = max(0, $preMcTotal - $preCorrect);

        return view('pages.courses.result', [
            'course' => $this->course,
            'pre' => $this->pretest,
            'post' => $this->posttest,
            'quizzes' => $this->quizzes,
            // Computed view data
            'prePct' => $prePct,
            'postPct' => $postPct,
            'mcPct' => $mcPct,
            'totalPct' => $totalPct,
            'delta' => $delta,
            'topAttempt' => $topAttempt,
            'topUnderReview' => $topUnderReview,
            'topPassed' => $topPassed,
            'isHistorical' => $isHistorical,
            'statusLabel' => $statusLabel,
            'statusColor' => $statusColor,
            'statusIcon' => $statusIcon,
            'mcTotal' => $mcTotal,
            'mcScore' => $mcScore,
            'maxAuto' => $maxAuto,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'essayCount' => $essayCount,
            'essayScore' => $essayScore,
            'maxManual' => $maxManual,
            'essayPending' => $essayPending,
            'attempts' => $attempts,
            'currentAttemptNum' => $currentAttemptNum,
            'preMcTotal' => $preMcTotal,
            'preCorrect' => $preCorrect,
            'preIncorrect' => $preIncorrect,
            // @intelephense-ignore-next-line -- Livewire registers the "layout" view macro at runtime.
        ])->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'result',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'completedModuleIds' => $completedModuleIds,
            'hasPosttestAttempt' => true, // Result page means posttest was attempted
            'courseId' => $this->course->id,
        ]);
    }
}
