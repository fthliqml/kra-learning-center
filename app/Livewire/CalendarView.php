<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;

class CalendarView extends Component
{
  public int $currentMonth;
  public int $currentYear;
  public array $events = [];
  public array $calendarDays = [];

  // Jump to month/year
  public bool $showJumpModal = false;
  public int $jumpMonth;
  public int $jumpYear;

  public function mount(array $events = [])
  {
    $this->currentMonth = now()->month;
    $this->currentYear = now()->year;
    $this->jumpMonth = $this->currentMonth;
    $this->jumpYear = $this->currentYear;
    $this->events = $events;
    $this->generateCalendarDays();
  }

  public function generateCalendarDays()
  {
    $this->calendarDays = [];

    $firstDayOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
    $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

    // Get the day of week for the first day (0 = Sunday, 6 = Saturday)
    $startDayOfWeek = $firstDayOfMonth->dayOfWeek;

    // Add empty slots for days before the first day of month
    for ($i = 0; $i < $startDayOfWeek; $i++) {
      $this->calendarDays[] = [
        'day' => null,
        'date' => null,
        'isToday' => false,
        'events' => [],
      ];
    }

    // Add all days of the month
    for ($day = 1; $day <= $lastDayOfMonth->day; $day++) {
      $date = Carbon::create($this->currentYear, $this->currentMonth, $day);
      $dateKey = $date->format('Y-m-d');

      $this->calendarDays[] = [
        'day' => $day,
        'date' => $dateKey,
        'isToday' => $date->isToday(),
        'events' => $this->events[$dateKey] ?? [],
      ];
    }

    // Fill remaining slots to complete the last week (optional, for consistent grid)
    $remainingSlots = 7 - (count($this->calendarDays) % 7);
    if ($remainingSlots < 7) {
      for ($i = 0; $i < $remainingSlots; $i++) {
        $this->calendarDays[] = [
          'day' => null,
          'date' => null,
          'isToday' => false,
          'events' => [],
        ];
      }
    }
  }

  public function goToNextMonth()
  {
    $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
    $this->currentMonth = $date->month;
    $this->currentYear = $date->year;
    $this->generateCalendarDays();
  }

  public function goToPrevMonth()
  {
    $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
    $this->currentMonth = $date->month;
    $this->currentYear = $date->year;
    $this->generateCalendarDays();
  }

  public function openJumpModal()
  {
    $this->jumpMonth = $this->currentMonth;
    $this->jumpYear = $this->currentYear;
    $this->showJumpModal = true;
  }

  public function closeJumpModal()
  {
    $this->showJumpModal = false;
  }

  public function jumpTo()
  {
    $this->currentMonth = $this->jumpMonth;
    $this->currentYear = $this->jumpYear;
    $this->generateCalendarDays();
    $this->showJumpModal = false;
  }

  public function goToToday()
  {
    $this->currentMonth = now()->month;
    $this->currentYear = now()->year;
    $this->generateCalendarDays();
  }

  public function getMonthNameProperty(): string
  {
    return Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F');
  }

  public function render()
  {
    return view('livewire.calendar-view');
  }
}
