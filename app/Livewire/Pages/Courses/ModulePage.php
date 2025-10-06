<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\Section;
use App\Models\Topic;
use App\Models\ResourceItem;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ModulePage extends Component
{
    public Course $course;
    public ?int $activeTopicId = null;
    public ?int $activeSectionId = null;

    public function mount(Course $course)
    {
        $userId = Auth::id();
        $assigned = $course->userCourses()->where('user_id', $userId)->exists();
        if (! $assigned) {
            abort(403, 'You are not assigned to this course.');
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

        // Initialize active topic & section
        $firstTopic = $this->course->learningModules->first();
        if ($firstTopic) {
            $this->activeTopicId = $firstTopic->id;
            $firstSection = $firstTopic->sections->first();
            $this->activeSectionId = $firstSection?->id;
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
    public function completeSubtopic(): void
    {
        $userId = Auth::id();
        if (!$userId) return;

        // Fetch enrollment
        $enrollment = $this->course->userCourses()->where('user_id', $userId)->first();
        if (!$enrollment) return;

        $totalUnits = $this->computeTotalUnits();
        if ($totalUnits <= 0) return;

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

        return view('pages.courses.module-page', [
            'course' => $this->course,
            'topics' => $this->course->learningModules,
            'activeTopic' => $activeTopic,
            'sections' => $activeTopic?->sections ?? collect(),
            'activeSection' => $activeSection,
            'videoResources' => $videoResources,
            'readingResources' => $readingResources,
        ])->layout('layouts.livewire.course', [
            'courseTitle' => $this->course->title,
            'stage' => 'module',
            'progress' => $this->course->progressForUser(),
            'stages' => ['pretest', 'module', 'posttest', 'result'],
            'modules' => $this->course->learningModules,
            'activeModuleId' => $this->activeTopicId,
            'activeSectionId' => $this->activeSectionId,
            'completedModuleIds' => [],
        ]);
    }
}
