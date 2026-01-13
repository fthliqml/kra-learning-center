<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use App\Models\SectionQuizQuestion;
use App\Models\SectionQuizAttempt;
use App\Models\SectionQuizAttemptAnswer;
use App\Models\TestAttempt;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModulePage extends Component
{
    public Course $course;
    public ?int $activeTopicId = null;
    public ?int $activeSectionId = null;

    // Quiz modal state
    public bool $showQuizModal = false;
    public ?int $quizSectionId = null;
    public array $quizQuestions = [];
    public bool $quizHasEssay = false;
    public ?array $quizResult = null; // e.g., ['score'=>3,'total'=>4,'passed'=>true,'hasEssay'=>false]

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
        if (! $assigned) {
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

        // Ensure enrollment for progress tracking exists and mark in_progress on first engagement
        $enrollment = null;
        if ($userId) {
            $enrollment = $course->userCourses()->firstOrCreate(
                ['user_id' => $userId],
                ['status' => 'in_progress', 'current_step' => 0]
            );
            // If existing with null/not_started, bump to in_progress
            if (($enrollment->status ?? '') === '' || strtolower($enrollment->status) === 'not_started') {
                $enrollment->status = 'in_progress';
                $enrollment->save();
            }
        }

        // NOTE: After passing, users are allowed to re-open learning materials in review mode.
        // Do not redirect completed/passed users away from modules.

        // Gate: require pretest submitted before accessing modules (if pretest exists)
        $pretest = Test::where('course_id', $course->id)->where('type', 'pretest')->select('id')->first();
        if ($pretest) {
            $done = TestAttempt::where('test_id', $pretest->id)
                ->where('user_id', $userId)
                ->whereIn('status', [TestAttempt::STATUS_SUBMITTED, TestAttempt::STATUS_UNDER_REVIEW])
                ->exists();
            if (! $done) {
                return redirect()->route('courses-pretest.index', ['course' => $course->id]);
            }
        }

        $this->course = $course->load(['learningModules' => function ($q) {
            $q->orderBy('id')->with(['sections' => function ($s) {
                $s->select('id', 'topic_id', 'title')
                    ->orderBy('id')
                    ->with(['resources' => function ($r) {
                        $r->select('id', 'section_id', 'content_type', 'url', 'filename');
                    }]);
            }]);
        }]);

        // Initialize active topic & section based on user's progress (resume)
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        $currentStep = (int) ($enrollment->current_step ?? 0);

        // Compute ordered sections across topics
        $orderedSections = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $sec) {
                $orderedSections[] = [$topic->id, $sec->id];
            }
        }
        $sectionsCount = count($orderedSections);

        if ($sectionsCount > 0) {
            // Allow deep-linking to a specific section or starting from first via query params
            $reqSectionId = (int) request()->query('section', 0);
            $start = (string) request()->query('start', '');

            if ($reqSectionId > 0) {
                // Find the topic for the given section id
                $found = null;
                foreach ($this->course->learningModules as $topic) {
                    $sec = $topic->sections->firstWhere('id', $reqSectionId);
                    if ($sec) {
                        $found = [$topic->id, $sec->id];
                        break;
                    }
                }
                if ($found) {
                    $this->activeTopicId = $found[0];
                    $this->activeSectionId = $found[1];
                }
            }

            if (!$this->activeTopicId || !$this->activeSectionId) {
                if (strtolower($start) === 'first') {
                    // Force first available section
                    $pair = $orderedSections[0];
                    $this->activeTopicId = $pair[0];
                    $this->activeSectionId = $pair[1];
                } else {
                    // Default resume behavior based on progress
                    $hasPretest = (bool) $pretest;
                    $pretestUnits = $hasPretest ? 1 : 0;
                    // Number of sections completed so far
                    $completedSections = max(0, min($currentStep - $pretestUnits, $sectionsCount));
                    // Target index is the next not-yet-done section, or last if all done
                    $targetIndex = ($completedSections < $sectionsCount) ? $completedSections : ($sectionsCount - 1);
                    $pair = $orderedSections[$targetIndex];
                    $this->activeTopicId = $pair[0];
                    $this->activeSectionId = $pair[1];
                }
            }
        } else {
            // Fallback to first topic if no sections
            $firstTopic = $this->course->learningModules->first();
            if ($firstTopic) {
                $this->activeTopicId = $firstTopic->id;
                $this->activeSectionId = $firstTopic->sections->first()?->id;
            }
        }

        // Lightweight server-side flag: auto-reopen quiz modal on refresh if previously opened
        $reopenQuizId = session()->get('reopen_quiz_section');
        if ($reopenQuizId && (int) $reopenQuizId === (int) $this->activeSectionId) {
            // Only reopen if section still has quiz, no remedial, and no attempt yet
            $hasSectionQuiz = SectionQuizQuestion::where('section_id', $this->activeSectionId)->exists();
            if ($hasSectionQuiz) {
                $isRemedial = false;
                $postRow = Test::where('course_id', $this->course->id)->where('type', 'posttest')->select('id')->first();
                if ($postRow) {
                    $lastAttempt = TestAttempt::where('test_id', $postRow->id)
                        ->where('user_id', $userId)
                        ->orderByDesc('submitted_at')->orderByDesc('id')
                        ->first();
                    if ($lastAttempt && !$lastAttempt->is_passed) {
                        $isRemedial = true;
                    }
                }

                $attemptExists = SectionQuizAttempt::where('section_id', $this->activeSectionId)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$isRemedial && !$attemptExists) {
                    $this->openQuizModalForSection($this->activeSectionId);
                }
            }
            // Keep the flag for subsequent refreshes while modal is considered open
        }
    }

    public function selectTopic(int $topicId): void
    {
        $topic = $this->course->learningModules->firstWhere('id', $topicId);
        if (!$topic) return;
        $this->activeTopicId = $topicId;
        $this->activeSectionId = $topic->sections->first()?->id;
    }

    public function selectSection(int $sectionId): void
    {
        // Ensure section belongs to active topic
        $topic = $this->course->learningModules->firstWhere('id', $this->activeTopicId);
        if (!$topic) return;
        $section = $topic->sections->firstWhere('id', $sectionId);
        if (!$section) return;
        $this->activeSectionId = $sectionId;
    }

    public function goBackStep(): mixed
    {
        $userId = Auth::id();
        if (!$userId) return null;

        // Compute ordered sections across topics with topic mapping
        $ordered = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $sec) {
                $ordered[] = ['topic_id' => (int) $topic->id, 'section_id' => (int) $sec->id];
            }
        }
        if (!$ordered) return null;

        $idx = null;
        foreach ($ordered as $i => $pair) {
            if ((int) $pair['section_id'] === (int) $this->activeSectionId) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) return null;

        // Normal case: go to previous section
        if ($idx > 0) {
            $prev = $ordered[$idx - 1];
            return redirect()->route('courses-modules.index', [
                'course' => $this->course->id,
                'section' => $prev['section_id'],
            ]);
        }

        // At the first section: allow going back to Pretest if it's retakable OR the user has passed (review mode)
        $hasPassedPosttest = false;
        $postRow = Test::where('course_id', $this->course->id)
            ->where('type', 'posttest')
            ->select(['id'])
            ->first();
        if ($postRow) {
            $lastAttempt = TestAttempt::where('test_id', $postRow->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            $hasPassedPosttest = (bool) ($lastAttempt?->is_passed);
        }

        $pretest = Test::where('course_id', $this->course->id)
            ->where('type', 'pretest')
            ->select(['id', 'max_attempts'])
            ->first();
        if (!$pretest) return null;

        if (!$hasPassedPosttest && $pretest->max_attempts !== null) {
            $maxAttempts = max(1, (int) $pretest->max_attempts);
            if ($maxAttempts <= 1) return null;
        }

        return redirect()->route('courses-pretest.index', ['course' => $this->course->id]);
    }

    /**
     * Increment progress by one sub-topic (Section) unit, bounded by total units.
     */
    public function completeSubtopic(): mixed
    {
        $userId = Auth::id();
        if (!$userId) return null;

        $totalUnits = $this->computeTotalUnits();
        if ($totalUnits <= 0) return null;

        // Gate: prevent progress outside schedule window
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

        // Determine if current position is the very last section/topic before posttest
        $isLastBeforePosttest = false;
        $orderedSections = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $sec) {
                $orderedSections[] = $sec->id;
            }
        }
        if (count($orderedSections) > 0) {
            $lastSectionId = end($orderedSections);
            $isLastBeforePosttest = (int) $this->activeSectionId === (int) $lastSectionId;
        } else {
            // Fallback: no sections, consider last topic as last unit before posttest
            $topics = $this->course->learningModules->values();
            if ($topics->count() > 0) {
                $lastTopicId = $topics->last()->id;
                $isLastBeforePosttest = (int) $this->activeTopicId === (int) $lastTopicId;
            }
        }

        // If this section has a quiz configured and user is not in remedial, show quiz first (do NOT advance progress yet)
        $hasSectionQuiz = SectionQuizQuestion::where('section_id', $this->activeSectionId)->exists();
        $isRemedial = false;
        $postRow = Test::where('course_id', $this->course->id)->where('type', 'posttest')->select('id')->first();
        if ($postRow) {
            $lastAttempt = TestAttempt::where('test_id', $postRow->id)
                ->where('user_id', Auth::id())
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            if ($lastAttempt && !$lastAttempt->is_passed) {
                $isRemedial = true;
            }
        }
        if ($hasSectionQuiz && !$isRemedial) {
            $this->openQuizModalForSection($this->activeSectionId);
            return null;
        }

        // No quiz (or remedial): advance progress now
        $this->advanceProgressForActiveSection($totalUnits);

        // If this was the last unit before posttest, go to posttest instead of modules
        if ($isLastBeforePosttest) {
            return redirect()->route('courses-posttest.index', ['course' => $this->course->id]);
        }

        // Otherwise, advance to next section/topic and return to modules
        $this->goToNextSection();
        if ($this->activeSectionId) {
            return redirect()->route('courses-modules.index', [
                'course' => $this->course->id,
                'section' => $this->activeSectionId,
            ]);
        }

        return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
    }

    public function openQuizModalForSection(int $sectionId): void
    {
        // Mark intent in session so refresh reopens the modal
        session()->put('reopen_quiz_section', $sectionId);
        $this->quizSectionId = $sectionId;
        $rows = SectionQuizQuestion::with(['options' => function ($q) {
            $q->orderBy('order')->select('id', 'question_id', 'option', 'is_correct', 'order');
        }])->where('section_id', $sectionId)
            ->orderBy('order')
            ->get();

        $collection = $rows->map(function ($q) {
            return [
                'id' => 'q' . $q->id,
                'db_id' => $q->id,
                'type' => $q->type,
                'text' => $q->question,
                'options' => $q->type === 'multiple'
                    ? $q->options->map(fn($o) => [
                        'id' => $o->id,
                        'text' => $o->option,
                    ])->values()->all()
                    : [],
            ];
        });
        $this->quizQuestions = $collection->values()->all();
        $this->quizHasEssay = $rows->contains(fn($q) => strtolower($q->type) !== 'multiple');
        $this->quizResult = null;
        $this->showQuizModal = true;
    }

    public function submitSectionQuiz(array $answers): void
    {
        $userId = Auth::id();
        if (!$userId) return;
        if (!$this->quizSectionId) return;

        // Gate: prevent quiz submit outside schedule window
        if (!$this->course->isAvailableForUser($userId)) {
            $this->closeQuizModalOnly();
            $postRow = Test::where('course_id', $this->course->id)->where('type', 'posttest')->select('id')->first();
            $hasPassedPosttest = false;
            if ($postRow) {
                $hasPassedPosttest = TestAttempt::where('test_id', $postRow->id)
                    ->where('user_id', $userId)
                    ->where('is_passed', true)
                    ->exists();
            }
            if ($hasPassedPosttest) {
                redirect()->route('courses-result.index', ['course' => $this->course->id])->send();
                return;
            }
            redirect()->route('courses-overview.show', ['course' => $this->course->id])->send();
            return;
        }

        $questionRows = SectionQuizQuestion::with('options')
            ->where('section_id', $this->quizSectionId)
            ->get()
            ->keyBy('id');
        if ($questionRows->isEmpty()) return;

        foreach ($questionRows as $qid => $q) {
            $key = 'q' . $qid;
            if (!array_key_exists($key, $answers) || ($answers[$key] === null || $answers[$key] === '')) {
                return; // client-side should prevent; silently ignore
            }
        }

        $now = now();
        $hasEssay = $questionRows->contains(fn($q) => strtolower($q->type) !== 'multiple');

        $items = [];
        DB::transaction(function () use ($userId, $answers, $questionRows, $now, $hasEssay, &$items) {
            $attempt = SectionQuizAttempt::create([
                'user_id' => $userId,
                'section_id' => $this->quizSectionId,
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
                $userAnswerText = null;
                $correctAnswerText = null;

                if (strtolower($q->type) === 'multiple') {
                    $selectedOptionId = (int) $value;
                    $opt = $q->options->firstWhere('id', $selectedOptionId);
                    $userAnswerText = $opt?->option;
                    $correctOpt = $q->options->firstWhere('is_correct', true);
                    $correctAnswerText = $correctOpt?->option;
                    if ($opt && (int) $opt->question_id === (int) $qid && ($opt->is_correct ?? false)) {
                        $isCorrect = true;
                        $points = 1;
                        $score += 1;
                    } else {
                        $isCorrect = false;
                        $points = 0;
                    }
                } else {
                    $answerText = (string) $value;
                    $userAnswerText = $answerText;
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
                    'order' => (int) ($q->order ?? 0),
                    'answered_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Build per-question feedback item
                $items[] = [
                    'id' => (string) ('q' . $qid),
                    'type' => strtolower($q->type),
                    'text' => (string) $q->question,
                    'is_correct' => $isCorrect, // true/false for MC, null for essay
                    'user_answer' => $userAnswerText,
                    'correct_answer' => $correctAnswerText,
                ];
            }

            if (!empty($inserts)) SectionQuizAttemptAnswer::insert($inserts);

            $attempt->score = $score;
            if (!$hasEssay) {
                $mcCount = $questionRows->where('type', 'multiple')->count();
                if ($mcCount > 0 && $score >= $mcCount) {
                    $attempt->passed = true;
                }
            }
            $attempt->save();

            $this->quizResult = [
                'score' => $score,
                'total' => (int) $questionRows->count(),
                'hasEssay' => $hasEssay,
                'items' => $items,
            ];
        });

        // Keep modal open to display the result
        $this->showQuizModal = true;
    }

    /**
     * Close quiz modal without advancing progress.
     * Used when user closes modal without submitting quiz.
     */
    public function closeQuizModalOnly(): void
    {
        $this->showQuizModal = false;
        $this->quizQuestions = [];
        $this->quizResult = null;
        $this->quizHasEssay = false;
        $this->quizSectionId = null;
        // Clear the reopen flag since user explicitly closed
        session()->forget('reopen_quiz_section');
    }

    /**
     * Close quiz modal and advance progress.
     * Only advances if quiz was actually submitted (attempt exists).
     */
    public function closeQuizModalAndAdvance(): mixed
    {
        $userId = Auth::id();
        $sectionId = $this->quizSectionId ?? $this->activeSectionId;

        // Gate: prevent progress outside schedule window
        if ($userId && !$this->course->isAvailableForUser($userId)) {
            $this->closeQuizModalOnly();
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

        // Check if quiz was actually submitted before advancing
        $hasQuizAttempt = SectionQuizAttempt::where('section_id', $sectionId)
            ->where('user_id', $userId)
            ->exists();

        if (!$hasQuizAttempt) {
            // Quiz not submitted - just close modal without advancing
            $this->closeQuizModalOnly();
            return null;
        }

        $this->showQuizModal = false;
        $this->quizQuestions = [];
        $this->quizResult = null;
        $this->quizHasEssay = false;
        $this->quizSectionId = null;
        // Clear the reopen flag; quiz flow is finished for this section
        session()->forget('reopen_quiz_section');
        // Advance progress now that quiz is completed for this section
        $this->advanceProgressForActiveSection($this->computeTotalUnits());
        // Determine if current active section is the last before posttest
        $orderedSections = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $sec) {
                $orderedSections[] = $sec->id;
            }
        }
        $isLastBeforePosttest = false;
        if (count($orderedSections) > 0) {
            $lastSectionId = end($orderedSections);
            $isLastBeforePosttest = (int) $this->activeSectionId === (int) $lastSectionId;
        }

        if ($isLastBeforePosttest) {
            // All sections done, go straight to posttest
            return redirect()->route('courses-posttest.index', ['course' => $this->course->id]);
        }

        // Otherwise move to the next section and reload modules on that section
        $this->goToNextSection();
        return redirect()->route('courses-modules.index', [
            'course' => $this->course->id,
            'section' => $this->activeSectionId,
        ]);
    }

    private function advanceProgressForActiveSection(int $totalUnits): void
    {
        $userId = Auth::id();
        if (!$userId) return;
        DB::transaction(function () use ($userId, $totalUnits) {
            $enrollment = $this->course->userCourses()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
            if (!$enrollment) return;

            $hasPretest = Test::where('course_id', $this->course->id)->where('type', 'pretest')->exists();
            $pretestUnits = $hasPretest ? 1 : 0;

            $orderedSections = [];
            foreach ($this->course->learningModules as $topic) {
                foreach ($topic->sections as $sec) {
                    $orderedSections[] = (int) $sec->id;
                }
            }

            $sectionIndex = array_search((int) $this->activeSectionId, $orderedSections, true);
            $targetStep = null;
            if ($sectionIndex !== false) {
                $targetStep = min($totalUnits, $pretestUnits + ($sectionIndex + 1));
            }

            $current = (int) ($enrollment->current_step ?? 0);
            if ($targetStep !== null) {
                if ($current < $targetStep) {
                    $enrollment->current_step = $targetStep;
                }
            } else {
                if ($current < $totalUnits) {
                    $enrollment->current_step = $current + 1;
                }
            }

            $newStep = (int) $enrollment->current_step;
            if ($newStep >= $totalUnits) {
                $enrollment->status = 'completed';
            } elseif ($newStep > 0) {
                $enrollment->status = 'in_progress';
            }
            $enrollment->save();
        });
    }

    private function goToNextSection(): void
    {
        $topics = $this->course->learningModules->values();
        if ($topics->isEmpty()) return;

        // Locate current topic index
        $topicIndex = $topics->search(fn($t) => (int)$t->id === (int)$this->activeTopicId);
        if ($topicIndex === false) {
            $topicIndex = 0;
        }
        $topic = $topics[$topicIndex];

        $sections = $topic->sections->values();
        $secIndex = $sections->search(fn($s) => (int)$s->id === (int)$this->activeSectionId);
        if ($secIndex === false) {
            $secIndex = -1;
        }

        // Next section within current topic
        if ($secIndex + 1 < $sections->count()) {
            $this->activeSectionId = $sections[$secIndex + 1]->id;
            return;
        }

        // Otherwise, advance to next topic with any section
        for ($i = $topicIndex + 1; $i < $topics->count(); $i++) {
            $t = $topics[$i];
            $secs = $t->sections->values();
            if ($secs->count() > 0) {
                $this->activeTopicId = $t->id;
                $this->activeSectionId = $secs[0]->id;
                return;
            }
        }
        // No further sections; remain on last section.
    }

    private function computeTotalUnits(): int
    {
        // Align with Course::progressUnitsCount(): pretest + sections (fallback topics) + posttest
        return (int) $this->course->progressUnitsCount();
    }

    public function render()
    {
        // Compute active entities and resources
        $activeTopic = $this->course->learningModules->firstWhere('id', $this->activeTopicId);
        $activeSection = $activeTopic?->sections->firstWhere('id', $this->activeSectionId);
        $videoResources = collect();
        $readingResources = collect();
        if ($activeSection) {
            // Map DB content types to UI buckets
            // DB enum: 'yt' (video embed), 'pdf' (reading/doc)
            $videoResources = $activeSection->resources
                ->filter(fn($r) => in_array(strtolower($r->content_type), ['video', 'yt'], true))
                ->values();
            $readingResources = $activeSection->resources
                ->filter(fn($r) => in_array(strtolower($r->content_type), ['reading', 'pdf'], true))
                ->values();
        }

        // Determine completed sections for current user to drive sidebar indicators
        $userId = Auth::id();
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->select(['id', 'user_id', 'course_id', 'current_step'])->first();
        $currentStep = (int) ($enrollment->current_step ?? 0);

        // Build ordered list of all sections across topics
        $orderedSectionRefs = [];
        foreach ($this->course->learningModules as $topic) {
            foreach ($topic->sections as $section) {
                $orderedSectionRefs[] = $section; // keep reference to mutate is_completed
            }
        }
        $sectionsTotal = count($orderedSectionRefs);
        $hasPretest = Test::where('course_id', $this->course->id)->where('type', 'pretest')->exists();
        $pretestUnits = $hasPretest ? 1 : 0;
        $completedCount = max(0, min($currentStep - $pretestUnits, $sectionsTotal));

        // Mark first N sections as completed
        for ($i = 0; $i < $completedCount; $i++) {
            if (isset($orderedSectionRefs[$i])) {
                $orderedSectionRefs[$i]->is_completed = true;
            }
        }

        // Compute completed module ids (all sections in module completed)
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

        // Eligibility: can access posttest when all prior units (pretest + sections/topics) are completed
        $totalUnits = (int) $this->course->progressUnitsCount();
        $eligibleForPosttest = $currentStep >= max(0, $totalUnits - 1);

        // Check if user has ANY posttest attempt (enables remedial navigation)
        $postRow = Test::where('course_id', $this->course->id)
            ->where('type', 'posttest')
            ->select(['id', 'max_attempts'])
            ->first();
        $hasPosttestAttempt = false;
        $canRetakePosttest = false;
        $hasPassedPosttest = false;
        if ($postRow) {
            $lastAttempt = TestAttempt::where('test_id', $postRow->id)
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')->orderByDesc('id')
                ->first();
            if ($lastAttempt) {
                $hasPosttestAttempt = true;
                $hasPassedPosttest = (bool) $lastAttempt->is_passed;
                if (!$lastAttempt->is_passed && $lastAttempt->status !== TestAttempt::STATUS_UNDER_REVIEW) {
                    $attemptCount = (int) TestAttempt::where('test_id', $postRow->id)
                        ->where('user_id', $userId)
                        ->whereIn('status', [
                            TestAttempt::STATUS_SUBMITTED,
                            TestAttempt::STATUS_UNDER_REVIEW,
                            TestAttempt::STATUS_EXPIRED,
                        ])
                        ->count();
                    if ($postRow->max_attempts === null) {
                        $canRetakePosttest = true;
                    } else {
                        $maxAttempts = max(1, (int) $postRow->max_attempts);
                        $canRetakePosttest = $attemptCount < $maxAttempts;
                    }
                }
            }
        }

        // Back button enablement: can go to previous section, or to pretest (if retakable OR passed/review mode)
        $canGoBackStep = false;
        $orderedIds = array_map(fn($s) => (int) $s->id, $orderedSectionRefs);
        if ($this->activeSectionId && $orderedIds) {
            $pos = array_search((int) $this->activeSectionId, $orderedIds, true);
            if ($pos !== false && $pos > 0) {
                $canGoBackStep = true;
            } elseif ($pos === 0) {
                if ($hasPassedPosttest) {
                    $canGoBackStep = true;
                } else {
                    $pretestRow = Test::where('course_id', $this->course->id)
                        ->where('type', 'pretest')
                        ->select(['id', 'max_attempts'])
                        ->first();
                    if ($pretestRow) {
                        if ($pretestRow->max_attempts === null) {
                            $canGoBackStep = true;
                        } else {
                            $maxAttempts = max(1, (int) $pretestRow->max_attempts);
                            $canGoBackStep = $maxAttempts > 1;
                        }
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

        // Determine if this is the last visible unit (for button label)
        $isLastSection = false;
        if (count($orderedSectionRefs) > 0) {
            $last = end($orderedSectionRefs);
            $isLastSection = $activeSection && $last && ((int) $activeSection->id === (int) $last->id);
        } else {
            $topicsList = $this->course->learningModules->values();
            $isLastSection = $activeTopic && $topicsList->isNotEmpty() && ((int) $activeTopic->id === (int) $topicsList->last()->id);
        }

        // Check if active section has a quiz
        $hasSectionQuiz = false;
        if ($activeSection) {
            $hasSectionQuiz = SectionQuizQuestion::where('section_id', $activeSection->id)->exists();
        }

        // Return page view and pass layout variables
        /** @var \Illuminate\View\View&\App\Support\Ide\LivewireViewMacros $view */
        $view = view('pages.courses.module-page', [
            'course' => $this->course,
            'topics' => $this->course->learningModules,
            'activeTopic' => $activeTopic,
            'sections' => $activeTopic?->sections ?? collect(),
            'activeSection' => $activeSection,
            'videoResources' => $videoResources,
            'readingResources' => $readingResources,
            'eligibleForPosttest' => $eligibleForPosttest,
            'isLastSection' => $isLastSection,
            'canRetakePosttest' => $canRetakePosttest,
            'hasPosttestAttempt' => $hasPosttestAttempt,
            'hasPassedPosttest' => $hasPassedPosttest,
            'hasSectionQuiz' => $hasSectionQuiz,
            'quizQuestions' => $this->quizQuestions,
            'quizResult' => $this->quizResult,
            'canGoBackStep' => $canGoBackStep,
        ]);

        return $view->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'module',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'activeModuleId' => $this->activeTopicId,
            'activeSectionId' => $this->activeSectionId,
            'completedModuleIds' => $completedModuleIds,
            'hasPosttestAttempt' => $hasPosttestAttempt,
            'canRetakePosttest' => $canRetakePosttest,
            'canRetakePretest' => $canRetakePretest,
            'hasPassedPosttest' => $hasPassedPosttest,
            'courseId' => $this->course->id,
        ]);
    }
}
