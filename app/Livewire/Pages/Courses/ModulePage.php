<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.livewire.course')]
class ModulePage extends Component
{
    public Course $course;
    public ?int $activeTopicId = null;
    public ?int $activeSectionId = null;

    public function mount(Course $course)
    {
        $userId = Auth::id();
        // Assigned via TrainingAssessment within the training schedule window
        $today = now()->startOfDay();
        $assigned = $course->trainings()
            ->where(function ($w) use ($today) {
                $w->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($w) use ($today) {
                $w->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->whereHas('assessments', function ($a) use ($userId) {
                $a->where('employee_id', $userId);
            })
            ->exists();
        if (! $assigned) {
            abort(403, 'You are not assigned to this course.');
        }

        // Ensure enrollment for progress tracking exists
        if ($userId) {
            $course->userCourses()->firstOrCreate(
                ['user_id' => $userId],
                ['status' => 'in_progress', 'current_step' => 0]
            );
        }

        // Gate: require pretest submitted before accessing modules (if pretest exists)
        $pretest = Test::where('course_id', $course->id)->where('type', 'pretest')->select('id')->first();
        if ($pretest) {
            $done = TestAttempt::where('test_id', $pretest->id)
                ->where('user_id', $userId)
                ->where('status', TestAttempt::STATUS_SUBMITTED)
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
            $hasPretest = (bool) $pretest;
            $pretestUnits = $hasPretest ? 1 : 0;
            // Number of sections completed so far
            $completedSections = max(0, min($currentStep - $pretestUnits, $sectionsCount));
            // Target index is the next not-yet-done section, or last if all done
            $targetIndex = ($completedSections < $sectionsCount) ? $completedSections : ($sectionsCount - 1);
            $pair = $orderedSections[$targetIndex];
            $this->activeTopicId = $pair[0];
            $this->activeSectionId = $pair[1];
        } else {
            // Fallback to first topic if no sections
            $firstTopic = $this->course->learningModules->first();
            if ($firstTopic) {
                $this->activeTopicId = $firstTopic->id;
                $this->activeSectionId = $firstTopic->sections->first()?->id;
            }
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

    /**
     * Increment progress by one sub-topic (Section) unit, bounded by total units.
     */
    public function completeSubtopic(): mixed
    {
        $userId = Auth::id();
        if (!$userId) return null;

        // Fetch enrollment
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) return null;

        $totalUnits = $this->computeTotalUnits();
        if ($totalUnits <= 0) return null;

        $current = (int) ($enrollment->current_step ?? 0);
        if ($current < $totalUnits) {
            $enrollment->current_step = $current + 1;
            // Update status heuristically
            if ($enrollment->current_step >= $totalUnits) {
                $enrollment->status = 'completed';
            } elseif ($enrollment->current_step > 0) {
                $enrollment->status = 'in_progress';
            }
            $enrollment->save();
        }

        // After completing, navigate to the next section (or next topic's first section)
        $this->goToNextSection();

        // Force a fresh render so layout/sidebars receive updated active IDs
        return redirect()->route('courses-modules.index', ['course' => $this->course->id]);
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

        // Return page view and pass layout variables via layoutData()
        return view('pages.courses.module-page', [
            'course' => $this->course,
            'topics' => $this->course->learningModules,
            'activeTopic' => $activeTopic,
            'sections' => $activeTopic?->sections ?? collect(),
            'activeSection' => $activeSection,
            'videoResources' => $videoResources,
            'readingResources' => $readingResources,
        ])->layoutData([
            'courseTitle' => $this->course->title,
            'stage' => 'module',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'activeModuleId' => $this->activeTopicId,
            'activeSectionId' => $this->activeSectionId,
            'completedModuleIds' => $completedModuleIds,
        ]);
    }
}
