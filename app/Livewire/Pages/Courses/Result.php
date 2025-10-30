<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Result extends Component
{
    public Course $course;

    public ?array $pretest = null;   // ['attempt' => TestAttempt|null, 'percent' => int|null, 'max' => int]
    public ?array $posttest = null;  // ['attempt' => TestAttempt|null, 'percent' => int|null, 'max' => int, 'passing' => int|null]

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
            $percent = null;
            if ($attempt && $maxAuto > 0) {
                $percent = (int) round(($attempt->auto_score / max(1, $maxAuto)) * 100);
            }
            $this->pretest = [
                'test' => $pre,
                'attempt' => $attempt,
                'max_auto' => $maxAuto,
                'percent' => $percent,
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
            $percent = null;
            if ($attempt && $maxAuto > 0) {
                $percent = (int) round(($attempt->auto_score / max(1, $maxAuto)) * 100);
            }
            $this->posttest = [
                'test' => $post,
                'attempt' => $attempt,
                'max_auto' => $maxAuto,
                'percent' => $percent,
                'passing' => $post->passing_score,
            ];
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
