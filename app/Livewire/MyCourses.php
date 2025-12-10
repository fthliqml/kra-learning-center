<?php

namespace App\Livewire;

use App\Models\UserCourse;
use Livewire\Component;

class MyCourses extends Component
{
    public $courses = [];

    public function mount()
    {
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $userId = auth()->id();

        // Get courses that user is actually assigned to via training assessments
        $this->courses = UserCourse::with([
            'course' => function ($q) use ($userId) {
                $q->whereHas('trainings', function ($t) use ($userId) {
                    $t->whereHas('assessments', function ($a) use ($userId) {
                        $a->where('employee_id', $userId);
                    });
                });
            }
        ])
            ->where('user_id', $userId)
            ->whereNotIn('status', ['completed', 'failed'])
            ->get()
            ->filter(function ($userCourse) {
                // Filter out courses that don't have the training assignment
                return $userCourse->course !== null;
            })
            ->map(function ($userCourse) {
                // Get progress from UserCourse attribute
                $progress = $userCourse->progress_percentage ?? 0;

                return [
                    'id' => $userCourse->course->id,
                    'thumbnail' => $userCourse->course->thumbnail_url ?? '/images/reporting.jpg',
                    'category' => $userCourse->course->group_comp ?? 'General',
                    'title' => $userCourse->course->title ?? 'Untitled Course',
                    'progress' => $progress,
                ];
            })
            ->take(5) // Show max 5 courses
            ->toArray();
    }

    public function render()
    {
        return view('livewire.my-courses');
    }
}
