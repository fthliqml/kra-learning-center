<?php

namespace App\Livewire\Components\Dashboard;

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

    // Show all courses the user has worked on, ordered by last activity (updated_at)
    // but only if the course is assigned to the user via training assessments.
    $this->courses = UserCourse::query()
      ->where('user_id', $userId)
      ->where('status', '!=', 'failed')
      ->whereHas('course.trainings.assessments', function ($a) use ($userId) {
        $a->where('employee_id', $userId);
      })
      ->with('course')
      ->orderByDesc('updated_at')
      ->get()
      ->map(function ($userCourse) {
        // Get progress from UserCourse attribute
        $progress = $userCourse->progress_percentage ?? 0;
        $isCompleted = ($userCourse->status === 'completed') || ((int) $progress >= 100);

        return [
          'id' => $userCourse->course->id,
          'thumbnail_url' => $userCourse->course->thumbnail_url,
          'category' => $userCourse->course->group_comp ?? 'General',
          'title' => $userCourse->course->title ?? 'Untitled Course',
          'progress' => $progress,
          'status' => $userCourse->status,
          'is_completed' => $isCompleted,
          'last_activity' => optional($userCourse->updated_at)->diffForHumans(),
          'last_activity_at' => optional($userCourse->updated_at)->toIso8601String(),
        ];
      })
      ->toArray();
  }

  public function render()
  {
    return view('components.dashboard.my-courses');
  }
}
