<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Section;
use App\Models\SectionQuizAttempt;
use App\Models\SectionQuizAttemptAnswer;
use App\Models\SectionQuizQuestion;
use App\Models\Test;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SectionQuiz extends Component
{
    public Course $course;
    public Section $section;
    public array $questions = [];

    public function mount(Course $course, Section $section)
    {
        $userId = Auth::id();
        if (!$userId) abort(401);

        // Must be assigned to this course via training assessment
        $assigned = $course->trainings()
            ->whereHas('assessments', function ($a) use ($userId) {
                $a->where('employee_id', $userId);
            })
            ->exists();
        if (!$assigned) abort(403);

        // Gate: before schedule start, user can only view overview
        if ($userId && !$course->isAvailableForUser($userId)) {
            return redirect()->route('courses-overview.show', ['course' => $course->id]);
        }

        // Ensure section belongs to the course
        $sec = $section;
        $topicIds = $course->learningModules()->pluck('id')->all();
        if (!in_array((int)$sec->topic_id, array_map('intval', $topicIds), true)) {
            abort(404);
        }
        $this->course = $course->load(['learningModules' => function ($q) {
            $q->orderBy('id')->with(['sections' => function ($s) {
                $s->select('id', 'topic_id', 'title')->orderBy('id');
            }]);
        }]);
        $this->section = $sec;

        // Gate: require that this section is already completed (based on progress step)
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) abort(403);
        $currentStep = (int) ($enrollment->current_step ?? 0);

        // Build ordered sections across topics
        $orderedSections = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $s) {
                $orderedSections[] = $s->id;
            }
        }
        $sectionsTotal = count($orderedSections);
        $hasPretest = Test::where('course_id', $this->course->id)->where('type', 'pretest')->exists();
        $preUnits = $hasPretest ? 1 : 0;
        $completedCount = max(0, min($currentStep - $preUnits, $sectionsTotal));
        $sectionIndex = array_search($this->section->id, $orderedSections, true);
        if ($sectionIndex === false || $sectionIndex >= $completedCount) {
            // Not yet eligible for this quiz
            return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
        }

        // Load questions for this section
        $rows = SectionQuizQuestion::with(['options' => function ($q) {
            $q->orderBy('order')->select('id', 'question_id', 'option', 'is_correct', 'order');
        }])->where('section_id', $this->section->id)
            ->orderBy('order')
            ->get();

        $collection = $rows->map(function ($q) {
            return [
                'id' => 'q' . $q->id,
                'db_id' => $q->id,
                'type' => $q->type, // 'multiple' or 'essay'
                'text' => $q->question,
                'options' => $q->type === 'multiple'
                    ? $q->options->map(fn($o) => [
                        'id' => $o->id,
                        'text' => $o->option,
                    ])->values()->all()
                    : [],
            ];
        });
        $this->questions = $collection->values()->all();
    }

    public function render()
    {
        // For sidebar completion indicators
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
            if (isset($orderedSectionRefs[$i])) $orderedSectionRefs[$i]->is_completed = true;
        }

        $completedModuleIds = [];
        foreach ($this->course->learningModules as $topic) {
            $secs = $topic->sections ?? collect();
            $count = $secs->count();
            if ($count === 0) continue;
            $doneInTopic = $secs->filter(fn($s) => !empty($s->is_completed))->count();
            if ($doneInTopic > 0 && $doneInTopic === $count) $completedModuleIds[] = $topic->id;
        }

        /** @var \Illuminate\View\View&\App\Support\Ide\LivewireViewMacros $view */
        $view = view('pages.courses.section-quiz', [
            'course' => $this->course,
            'section' => $this->section,
            'questions' => $this->questions,
        ]);

        return $view->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'module',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'activeModuleId' => $this->section->topic_id,
            'activeSectionId' => $this->section->id,
            'completedModuleIds' => $completedModuleIds,
        ]);
    }

    public function submitQuiz(array $answers)
    {
        $userId = Auth::id();
        if (!$userId) abort(401);

        $questionRows = SectionQuizQuestion::with('options')
            ->where('section_id', $this->section->id)
            ->get()
            ->keyBy('id');
        if ($questionRows->isEmpty()) abort(422, 'Tidak ada soal untuk quiz ini.');

        foreach ($questionRows as $qid => $q) {
            $key = 'q' . $qid;
            if (!array_key_exists($key, $answers) || ($answers[$key] === null || $answers[$key] === '')) {
                abort(422, 'Beberapa jawaban masih kosong.');
            }
        }

        $now = now();
        $hasEssay = $questionRows->contains(fn($q) => strtolower($q->type) !== 'multiple');

        DB::transaction(function () use ($userId, $answers, $questionRows, $now, $hasEssay) {
            $attempt = SectionQuizAttempt::create([
                'user_id' => $userId,
                'section_id' => $this->section->id,
                'score' => 0,
                'total_questions' => $questionRows->count(),
                'passed' => false,
                'started_at' => $now,
                'completed_at' => $now,
            ]);

            $score = 0;
            $inserts = [];
            foreach ($answers as $frontendKey => $value) {
                if (!str_starts_with($frontendKey, 'q')) continue;
                $qid = (int) substr($frontendKey, 1);
                if (!$qid || !isset($questionRows[$qid])) continue;
                $q = $questionRows[$qid];

                $selectedOptionId = null;
                $answerText = null;
                $isCorrect = null;
                $points = 0;

                if (strtolower($q->type) === 'multiple') {
                    $selectedOptionId = (int) $value;
                    $opt = $q->options->firstWhere('id', $selectedOptionId);
                    if (!$opt || (int) $opt->question_id !== (int) $qid) abort(422, 'Opsi tidak valid.');
                    if ($opt->is_correct) {
                        $isCorrect = true;
                        $points = 1;
                        $score += 1;
                    } else {
                        $isCorrect = false;
                        $points = 0;
                    }
                } else {
                    $answerText = (string) $value;
                    $points = 0;
                    $isCorrect = null;
                }

                $inserts[] = [
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $qid,
                    'selected_option_id' => $selectedOptionId,
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $points,
                    // Persist display order to satisfy NOT NULL constraint
                    'order' => (int) ($q->order ?? 0),
                    'answered_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($inserts)) SectionQuizAttemptAnswer::insert($inserts);

            $attempt->score = $score;
            // If there are no essays and all MC correct, mark as passed (optional)
            if (!$hasEssay) {
                $mcCount = $questionRows->where('type', 'multiple')->count();
                if ($mcCount > 0 && $score >= $mcCount) {
                    $attempt->passed = true;
                }
            }
            $attempt->save();
        });

        // After submit, navigate directly to the next material (next section or posttest if none)
        // Compute ordered sections across the course
        $orderedSections = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $s) {
                $orderedSections[] = $s->id;
            }
        }
        $nextSectionId = null;
        if (!empty($orderedSections)) {
            $idx = array_search($this->section->id, $orderedSections, true);
            if ($idx !== false && ($idx + 1) < count($orderedSections)) {
                $nextSectionId = $orderedSections[$idx + 1];
            }
        }

        if ($nextSectionId) {
            return redirect()->route('courses-modules.index', ['course' => $this->course->id, 'section' => $nextSectionId]);
        }
        // No more sections: go to posttest directly
        return redirect()->route('courses-posttest.index', ['course' => $this->course->id]);
    }
}
