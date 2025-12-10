<?php

namespace App\Livewire;

use App\Models\UserCourse;
use Carbon\Carbon;
use Livewire\Component;

class WelcomeBanner extends Component
{
  public string $userName = '';
  public int $completedCoursesThisMonth = 0;

  public function mount()
  {
    $this->userName = auth()->user()->name ?? 'User';
    $this->loadCompletedCourses();
  }

  public function loadCompletedCourses()
  {
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    $this->completedCoursesThisMonth = UserCourse::where('user_id', auth()->id())
      ->where('status', 'completed')
      ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
      ->count();
  }

  public function render()
  {
    return view('livewire.welcome-banner');
  }
}
