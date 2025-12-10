<?php

namespace App\Livewire\Pages\dashboard;

use App\Models\Certification;
use App\Models\Training;
use Carbon\Carbon;
use Livewire\Component;

class EmployeeDashboard extends Component
{
  // Calendar events
  public array $calendarEvents = [];

  public function mount()
  {
    $this->loadCalendarEvents();
  }

  public function loadCalendarEvents()
  {
    $this->calendarEvents = [];

    // Get trainings for current month and next 2 months
    $startDate = now()->startOfMonth();
    $endDate = now()->addMonths(2)->endOfMonth();

    $userId = auth()->id();

    // Employee sees trainings where they are a participant
    $trainings = Training::with(['sessions.trainer.user'])
      ->whereBetween('start_date', [$startDate, $endDate])
      ->whereHas('assessments', function ($q) use ($userId) {
        $q->where('employee_id', $userId);
      })
      ->get();

    foreach ($trainings as $training) {
      $dateKey = $training->start_date->format('Y-m-d');

      if (!isset($this->calendarEvents[$dateKey])) {
        $this->calendarEvents[$dateKey] = [];
      }

      // Get first session details
      $firstSession = $training->sessions->first();
      $trainerName = $firstSession?->trainer?->user?->name ?? 'TBA';
      $location = $firstSession?->room_location ?? 'TBA';
      $time = $firstSession ? ($firstSession->start_time ? substr($firstSession->start_time, 0, 5) : 'TBA') . ' - ' . ($firstSession->end_time ? substr($firstSession->end_time, 0, 5) : 'TBA') : 'TBA';

      $this->calendarEvents[$dateKey][] = [
        'title' => $training->name ?? 'Training',
        'type' => $training->status === 'pending' ? 'warning' : 'normal',
        'trainer' => $trainerName,
        'location' => $location,
        'time' => $time,
      ];
    }
  }

  public function render()
  {
    return view('pages.dashboard.employee-dashboard');
  }
}
