<?php

namespace App\Livewire\Pages\Courses;

use App\Models\Course;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Courses extends Component
{
    use WithPagination;

    // Filters/search bound from the view
    public string $search = '';
    public ?string $filter = null;
    public int $perPage = 12;

    // Options for the filter dropdown
    public $groupOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    // Reset to the first page when search/filter changes
    public function updated($property): void
    {
        if (!is_array($property) && $property !== '') {
            $this->resetPage();
        }
    }

    #[Computed]
    public function courses()
    {
        // Simple per-request cache to avoid duplicate queries (courses() + resultsText())
        static $cached;
        if ($cached) {
            return $cached;
        }
        $userId = Auth::id();

        $query = Course::query()
            ->with([
                'competency:id,type',
                // Load only the current user's enrollment (if any) for progress bar without N+1
                'userCourses' => function ($q) use ($userId) {
                    if ($userId) {
                        $q->where('user_id', $userId)->select(['id', 'user_id', 'course_id', 'current_step', 'status']);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                },
            ])
            // Provide counts for UI metadata
            ->withCount(['learningModules as learning_modules_count', 'users'])
            // Only courses assigned to the logged-in user via TrainingAssessment within schedule window
            ->when($userId, function ($q) use ($userId) {
                $q->assignedToUser($userId);
            })
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                if ($term !== '') {
                    $q->where('title', 'like', "%{$term}%");
                }
            })
            // Filter by course group_comp directly (no training relation)
            ->when($this->filter, function ($q) {
                $value = trim((string) $this->filter);
                if ($value !== '') {
                    $q->whereHas('competency', fn($qq) => $qq->where('type', $value));
                }
            })
            ->orderBy('created_at', 'desc');

        $cached = $query->paginate($this->perPage)->onEachSide(1);

        // Map each course to add a computed progress_percent for current user using model method
        $user = Auth::user();
        $cached->getCollection()->transform(function ($course) use ($user) {
            $course->progress_percent = $course->progressForUser($user);
            return $course;
        });
        return $cached;
    }

    #[Computed]
    public function resultsText(): string
    {
        $courses = $this->courses();

        $hasPaginator = is_object($courses) && method_exists($courses, 'total');
        $total = $hasPaginator ? $courses->total() : (is_countable($courses) ? count($courses) : 0);
        $from = $hasPaginator ? ($total ? ($courses->firstItem() ?? 0) : 0) : ($total ? 1 : 0);
        $to = $hasPaginator ? ($total ? ($courses->lastItem() ?? 0) : 0) : $total;

        return $total ? "Showing {$from}–{$to} of {$total} results" : '';
    }

    public function render()
    {
        return view('pages.courses.courses', [
            'courses' => $this->courses(),
            'resultsText' => $this->resultsText(),
        ]);
    }
}
