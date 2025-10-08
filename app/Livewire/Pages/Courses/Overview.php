<?php

namespace App\Livewire\Pages\Courses;

use Livewire\Component;
use App\Models\Course;

class Overview extends Component
{
    public Course $course;
    public $modules;
    public int $modulesCount = 0;
    public int $assignUsers = 0;
    public int $durationDays = 0;
    public string $accordion = '';

    public function mount(Course $course)
    {
        $this->course = $course->load([
            'learningModules.sections.resources' => function ($q) {
                // simple ordering for consistency
                $q->orderBy('id');
            },
            'learningModules.sections' => function ($q) {
                $q->orderBy('id');
            },
            'learningModules' => function ($q) {
                $q->orderBy('id');
            },
        ]);

        // Map modules with derived counts
        $this->modules = $this->course->learningModules->map(function ($m) {
            $sections = $m->sections ?? collect();
            $sectionCount = $sections->count();
            $allResources = $sections->flatMap(fn($s) => $s->resources ?? collect());
            $resourceCount = $allResources->count();
            $videoCount = $allResources->where('content_type', 'yt')->count();
            $readingCount = $allResources->whereIn('content_type', ['pdf', 'doc', 'docx'])->count();

            // Placeholder completion (future: compute from user progress)
            $completedSections = 0; // to be replaced later

            $m->derived_section_count = $sectionCount;
            $m->derived_resource_count = $resourceCount;
            $m->derived_video_count = $videoCount;
            $m->derived_reading_count = $readingCount;
            $m->derived_completed_sections = $completedSections;

            // Build compact formatted counts (hide zeros later in Blade)
            $counts = [
                'sections' => $sectionCount,
                'videos'   => $videoCount,
                'readings' => $readingCount,
            ];
            $m->formatted_counts = $counts; // associative array for flexibility
            return $m;
        });

        $this->modulesCount = $this->modules->count();
        $this->assignUsers = $this->course->users()->count();

        // Set default opened accordion item (first module) if exists
        $first = $this->modules->first();
        $this->accordion = $first ? 'module-' . $first->id : '';
    }

    public function render()
    {
        return view('pages.courses.overview');
    }
}
