<?php

namespace App\Livewire\Pages\dashboard;

use App\Models\Certification;
use App\Models\Request;
use App\Models\Training;
use Carbon\Carbon;
use Livewire\Component;

class AdminDashboard extends Component
{
  public $selectedMonth = null;
  public $selectedYear = null;

  // Chart data
  public array $monthlyTrainingData = [];
  public array $monthLabels = [];

  // Selected month breakdown
  public array $monthBreakdown = [];

  // Stats cards
  public int $totalTrainingThisYear = 0;
  public int $upcomingSchedules = 0;
  public int $totalEmployees = 0;

  // Calendar events
  public array $calendarEvents = [];

  public function mount()
  {
    $this->selectedYear = now()->year;
    $this->loadChartData();
    $this->loadStatsCards();
    $this->loadCalendarEvents();
  }

  public function loadCalendarEvents()
  {
    $this->calendarEvents = [];

    // Get trainings for current month and next 2 months
    $startDate = now()->startOfMonth();
    $endDate = now()->addMonths(2)->endOfMonth();

    $trainings = Training::with(['sessions.trainer.user'])
      ->whereBetween('start_date', [$startDate, $endDate])
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

  public function loadChartData()
  {
    $year = $this->selectedYear;
    $monthlyData = [];
    $labels = [];

    // Generate data for 12 months
    for ($month = 1; $month <= 12; $month++) {
      $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
      $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

      // Count trainings that started in this month
      $count = Training::whereBetween('start_date', [$startOfMonth, $endOfMonth])->count();

      $monthlyData[] = $count;
      $labels[] = $startOfMonth->format('M');
    }

    $this->monthlyTrainingData = $monthlyData;
    $this->monthLabels = $labels;
  }

  public function loadStatsCards()
  {
    $now = Carbon::now();
    $startOfYear = $now->copy()->startOfYear();
    $endOfYear = $now->copy()->endOfYear();

    // Total training this year - all trainings in current year
    $this->totalTrainingThisYear = Training::whereBetween('start_date', [$startOfYear, $endOfYear])->count();

    // Upcoming schedules - trainings with start_date >= today
    $this->upcomingSchedules = Training::where('start_date', '>=', $now->startOfDay())->count();

    // Total employees (users with role employee)
    $this->totalEmployees = \App\Models\User::where('role', 'employee')->count();
  }

  public function selectMonth($monthIndex)
  {
    // monthIndex is 0-based from ApexCharts
    $this->selectedMonth = $monthIndex + 1;
    $this->monthBreakdown = [];

    $this->loadMonthBreakdown();

    $monthName = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->format('F Y');

    // Dispatch event to notify other components
    $this->dispatch('month-selected', [
      'month' => $this->selectedMonth,
      'year' => $this->selectedYear,
      'monthName' => $monthName,
    ]);

    // Dispatch event to update donut charts with breakdown data
    $this->dispatch(
      'breakdown-loaded',
      byType: $this->monthBreakdown['byType'] ?? [],
      byGroupComp: $this->monthBreakdown['byGroupComp'] ?? [],
      total: $this->monthBreakdown['total'] ?? 0
    );
  }

  public function closeMonthDetails()
  {
    $this->selectedMonth = null;
    $this->monthBreakdown = [];
  }

  public function loadMonthBreakdown()
  {
    if (!$this->selectedMonth) {
      $this->monthBreakdown = [];
      return;
    }

    $startOfMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
    $endOfMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

    // Get trainings for selected month
    $trainings = Training::whereBetween('start_date', [$startOfMonth, $endOfMonth])->get();

    // Breakdown by type
    $byType = $trainings->groupBy('type')->map(fn($items) => $items->count())->toArray();

    // Breakdown by group_comp
    $byGroupComp = $trainings->groupBy('group_comp')->map(fn($items) => $items->count())->toArray();

    $this->monthBreakdown = [
      'total' => $trainings->count(),
      'byType' => $byType,
      'byGroupComp' => $byGroupComp,
    ];
  }

  public function render()
  {
    return view('pages.dashboard.admin-dashboard');
  }
}
