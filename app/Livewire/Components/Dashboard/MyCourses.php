<?php

namespace App\Livewire\Components\Dashboard;

use App\Models\Course;
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
    $user = auth()->user();

    // Get all courses assigned to the user via training assessments (same logic as Courses page)
    $assignedCourses = Course::query()
      ->assignedToUser($userId)
      ->with([
        'competency',
        'userCourses' => function ($q) use ($userId) {
          $q->where('user_id', $userId);
        }
      ])
      ->withCount('learningModules as learning_modules_count')
      ->get();

    $this->courses = $assignedCourses
      ->map(function ($course) use ($user, $userId) {
        // Get UserCourse record if exists
        $userCourse = $course->userCourses->first();

        $activityAt = $userCourse?->updated_at ?? $userCourse?->created_at;

        // Calculate progress
        $progress = $course->progressForUser($user);
        $status = $userCourse?->status ?? 'not_started';
        $isCompleted = ($status === 'completed') || ((int) $progress >= 100);

        return [
          'id' => $course->id,
          'thumbnail_url' => $course->thumbnail_url,
          'category' => $course->competency?->type ?? $course->group_comp ?? 'General',
          'title' => $course->title ?? 'Untitled Course',
          'progress' => $progress,
          'status' => $status,
          'is_completed' => $isCompleted,
          'last_activity' => $activityAt ? $activityAt->diffForHumans() : null,
          'last_activity_at' => $activityAt ? $activityAt->toIso8601String() : null,
        ];
      })
      // Dashboard history: show newest activity first
      ->sortByDesc(function ($c) {
        return $c['last_activity_at'] ? strtotime($c['last_activity_at']) : 0;
      })
      ->take(3)
      ->values()
      ->toArray();
  }

  public function render()
  {
    return view('components.dashboard.my-courses');
  }
}
