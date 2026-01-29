<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Certification;
use App\Models\Request;
use App\Models\Training;
use App\Models\TrainingPlan;
use App\Models\SelfLearningPlan;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LeaderDashboard extends Component
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
  public int $pendingApprovals = 0;

  // Calendar events
  public array $calendarEvents = [];

  public function mount()
  {
    $this->selectedYear = now()->year;
    $this->loadChartData();
    $this->loadStatsCards();
    $this->loadCalendarEvents();
  }

  /**
   * Get available years for selection (current year and 5 years back)
   */
  public function getAvailableYearsProperty(): array
  {
    $currentYear = now()->year;
    $years = [];
    for ($i = 0; $i <= 5; $i++) {
      $years[] = $currentYear - $i;
    }
    return $years;
  }

  /**
   * Navigate to previous year
   */
  public function previousYear()
  {
    $minYear = now()->year - 5;
    if ($this->selectedYear > $minYear) {
      $this->selectedYear--;
      $this->selectedMonth = null;
      $this->monthBreakdown = [];
      $this->loadChartData();
      $this->dispatch('year-changed', year: $this->selectedYear);
    }
  }

  /**
   * Navigate to next year
   */
  public function nextYear()
  {
    $maxYear = now()->year;
    if ($this->selectedYear < $maxYear) {
      $this->selectedYear++;
      $this->selectedMonth = null;
      $this->monthBreakdown = [];
      $this->loadChartData();
      $this->dispatch('year-changed', year: $this->selectedYear);
    }
  }

  /**
   * Set specific year
   */
  public function setYear($year)
  {
    $year = (int) $year;
    $minYear = now()->year - 5;
    $maxYear = now()->year;

    if ($year >= $minYear && $year <= $maxYear) {
      $this->selectedYear = $year;
      $this->selectedMonth = null;
      $this->monthBreakdown = [];
      $this->loadChartData();
      $this->dispatch('year-changed', year: $this->selectedYear);
    }
  }

  /**
   * Hook for wire:model.live on selectedYear
   */
  public function updatedSelectedYear($value)
  {
    $this->selectedMonth = null;
    $this->monthBreakdown = [];
    $this->loadChartData();
    $this->dispatch('year-changed', year: $this->selectedYear);
  }

  public function loadCalendarEvents()
  {
    $this->calendarEvents = [];

    // Get trainings for current month and next 2 months
    $startDate = now()->startOfMonth();
    $endDate = now()->addMonths(2)->endOfMonth();

    $userId = Auth::id();
    $user = Auth::user();

    // Calendar should only show schedules that belong to the logged-in user:
    // - trainings where they are a trainer OR a participant
    // - certifications where they are a participant
    if (!$userId || !$user) {
      return;
    }

    $trainings = Training::with(['sessions.trainer.user'])
      ->whereBetween('start_date', [$startDate, $endDate])
      ->where(function ($q) use ($userId) {
        $q->whereHas('sessions.trainer', function ($tq) use ($userId) {
          $tq->where('user_id', $userId);
        })->orWhereHas('assessments', function ($aq) use ($userId) {
          $aq->where('employee_id', $userId);
        });
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
        'category' => 'training',
        'trainer' => $trainerName,
        'location' => $location,
        'time' => $time,
      ];
    }

    // Only show certification schedules where the user is a participant
    $certifications = Certification::with(['sessions'])
      ->whereHas('participants', function ($q) use ($userId) {
        $q->where('employee_id', $userId);
      })
      ->whereHas('sessions', function ($q) use ($startDate, $endDate) {
        $q->whereBetween('date', [$startDate, $endDate]);
      })
      ->get();

    foreach ($certifications as $certification) {
      foreach ($certification->sessions as $session) {
        if ($session->date < $startDate || $session->date > $endDate) {
          continue;
        }

        $dateKey = $session->date->format('Y-m-d');

        if (!isset($this->calendarEvents[$dateKey])) {
          $this->calendarEvents[$dateKey] = [];
        }

        $sessionTypeLabel = match ($session->type) {
          'theory' => 'Theory',
          'practical' => 'Practical',
          default => ucfirst($session->type ?? ''),
        };

        $time = $session->start_time
          ? substr($session->start_time, 0, 5) . ' - ' . ($session->end_time ? substr($session->end_time, 0, 5) : 'TBA')
          : 'TBA';

        $this->calendarEvents[$dateKey][] = [
          'title' => ($certification->name ?? 'Certification') . ' - ' . $sessionTypeLabel,
          'type' => 'certification',
          'category' => 'certification',
          'trainer' => $sessionTypeLabel . ' Session',
          'location' => $session->location ?? 'TBA',
          'time' => $time,
        ];
      }
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

      // Count trainings that started in this month (exclude cancelled and rejected)
      $count = Training::whereBetween('start_date', [$startOfMonth, $endOfMonth])
        ->whereNotIn('status', ['cancelled', 'rejected'])
        ->count();

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

    /** @var User|null $user */
    $user = Auth::user();

    // Total training this year - all trainings in current year (exclude cancelled and rejected)
    $this->totalTrainingThisYear = Training::whereBetween('start_date', [$startOfYear, $endOfYear])
      ->whereNotIn('status', ['cancelled', 'rejected'])
      ->count();

    // Upcoming schedules - trainings with start_date >= today (exclude cancelled and rejected)
    $this->upcomingSchedules = Training::where('start_date', '>=', $now->startOfDay())
      ->whereNotIn('status', ['cancelled', 'rejected'])
      ->count();

    // Pending approvals (actionable for the logged-in user)
    if (!$user) {
      $this->pendingApprovals = 0;
      return;
    }

    $pendingCertifications = $this->countActionableCertifications($user);
    $pendingTrainings = $this->countActionableTrainings($user);
    $pendingRequests = $this->countActionableTrainingRequests($user);
    $pendingIdp = $this->countActionableIdpPlans($user);

    $this->pendingApprovals = $pendingCertifications + $pendingTrainings + $pendingRequests + $pendingIdp;
  }

  private function isLidSectionHead(User $user): bool
  {
    return $user->hasPosition('section_head')
      && strtolower(trim($user->section ?? '')) === 'lid';
  }

  private function isLidDeptHead(User $user): bool
  {
    return $user->hasPosition('department_head')
      && trim((string) ($user->department ?? '')) === 'Human Capital, General Service, Security & LID';
  }

  private function countActionableTrainings(User $user): int
  {
    if ($this->isLidSectionHead($user)) {
      return Training::query()
        ->where('status', 'done')
        ->whereNull('section_head_signed_at')
        ->whereNull('dept_head_signed_at')
        ->count();
    }

    if ($this->isLidDeptHead($user)) {
      return Training::query()
        ->where('status', 'done')
        ->whereNotNull('section_head_signed_at')
        ->whereNull('dept_head_signed_at')
        ->count();
    }

    return 0;
  }

  private function countActionableCertifications(User $user): int
  {
    if (!$this->isLidSectionHead($user)) {
      return 0;
    }

    // In certification approval page, status 'completed' is treated as pending.
    return Certification::query()
      ->where('status', 'completed')
      ->count();
  }

  private function countActionableTrainingRequests(User $user): int
  {
    $base = Request::query()->where('status', 'pending');
    $userDept = strtolower(trim((string) ($user->department ?? '')));
    $userDiv = strtolower(trim((string) ($user->division ?? '')));

    if ($user->hasPosition('department_head') && $userDept !== '') {
      return $base
        ->where('approval_stage', Request::STAGE_DEPT_HEAD)
        ->whereHas('user', function ($q) use ($userDept) {
          $q->whereRaw('LOWER(TRIM(department)) = ?', [$userDept]);
        })
        ->count();
    }

    if ($user->hasPosition('division_head') && $userDiv !== '') {
      $isLidDiv = $userDiv === 'human capital, finance & general support';

      if ($isLidDiv) {
        return $base
          ->where('approval_stage', Request::STAGE_LID_DIV_HEAD)
          ->count();
      }

      return $base
        ->where('approval_stage', Request::STAGE_AREA_DIV_HEAD)
        ->whereHas('user', function ($q) use ($userDiv) {
          $q->whereRaw('LOWER(TRIM(division)) = ?', [$userDiv]);
        })
        ->count();
    }

    return 0;
  }

  private function expectedIdpStatusForUser(User $user): string
  {
    $section = strtolower(trim((string) ($user->section ?? '')));

    if ($user->hasPosition('section_head') && $section === 'lid') {
      return 'pending_lid';
    }

    if ($user->hasPosition('supervisor')) {
      return 'pending_spv';
    }

    if ($user->hasPosition('section_head') && $section !== 'lid') {
      return 'pending_section_head';
    }

    if ($user->hasPosition('department_head') && $section !== 'lid') {
      return 'pending_dept_head';
    }

    return '';
  }

  private function isSupervisorArea(): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    return $user ? $user->hasPosition('supervisor') : false;
  }

  private function isSectionHeadArea(): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    $section = strtolower(trim($user->section ?? ''));
    return $user->hasPosition('section_head') && $section !== 'lid';
  }

  private function isDeptHeadArea(): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    $section = strtolower(trim($user->section ?? ''));
    return $user->hasPosition('department_head') && $section !== 'lid';
  }

  private function isLidApprover(): bool
  {
    /** @var User|null $user */
    $user = Auth::user();
    return $user ? ($user->hasPosition('section_head') && strtolower(trim($user->section ?? '')) === 'lid') : false;
  }

  private function existsSupervisorInUserArea(User $target): bool
  {
    $section = (string) ($target->section ?? '');
    $department = (string) ($target->department ?? '');

    if ($section !== '') {
      return User::query()
        ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
        ->where('section', $section)
        ->exists();
    }

    if ($department !== '') {
      return User::query()
        ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
        ->where('department', $department)
        ->exists();
    }

    return false;
  }

  private function canApproveUserAtStatus(int $targetUserId, string $expectedStatus): bool
  {
    if ($expectedStatus === 'pending_lid') {
      return $this->isLidApprover();
    }

    if ($expectedStatus === 'pending_dept_head') {
      return $this->isDeptHeadArea();
    }

    if (!in_array($expectedStatus, ['pending_spv', 'pending_section_head'], true)) {
      return false;
    }

    $target = User::find($targetUserId);
    if (!$target) {
      return false;
    }

    $targetPosition = strtolower(trim($target->position ?? ''));

    if ($expectedStatus === 'pending_spv') {
      return $this->isSupervisorArea() && $targetPosition !== 'supervisor';
    }

    if ($expectedStatus === 'pending_section_head' && $this->isSectionHeadArea()) {
      if ($targetPosition === 'supervisor') {
        return true;
      }

      return !$this->existsSupervisorInUserArea($target);
    }

    return false;
  }

  private function countActionableIdpPlans(User $user): int
  {
    $expectedStatus = $this->expectedIdpStatusForUser($user);
    if ($expectedStatus === '') {
      return 0;
    }

    // Fast-path: LID approver can approve all pending_lid.
    if ($expectedStatus === 'pending_lid') {
      return TrainingPlan::where('status', 'pending_lid')->count()
        + SelfLearningPlan::where('status', 'pending_lid')->count()
        + MentoringPlan::where('status', 'pending_lid')->count()
        + ProjectPlan::where('status', 'pending_lid')->count();
    }

    // Otherwise, filter per-target rules.
    $candidateUserIds = collect()
      ->merge(TrainingPlan::where('status', $expectedStatus)->pluck('user_id'))
      ->merge(SelfLearningPlan::where('status', $expectedStatus)->pluck('user_id'))
      ->merge(MentoringPlan::where('status', $expectedStatus)->pluck('user_id'))
      ->merge(ProjectPlan::where('status', $expectedStatus)->pluck('user_id'))
      ->unique()
      ->filter(fn($id) => (int) $id > 0)
      ->values();

    if ($candidateUserIds->isEmpty()) {
      return 0;
    }

    $allowedUserIds = $candidateUserIds
      ->filter(fn($id) => $this->canApproveUserAtStatus((int) $id, $expectedStatus))
      ->values();

    if ($allowedUserIds->isEmpty()) {
      return 0;
    }

    return TrainingPlan::where('status', $expectedStatus)->whereIn('user_id', $allowedUserIds)->count()
      + SelfLearningPlan::where('status', $expectedStatus)->whereIn('user_id', $allowedUserIds)->count()
      + MentoringPlan::where('status', $expectedStatus)->whereIn('user_id', $allowedUserIds)->count()
      + ProjectPlan::where('status', $expectedStatus)->whereIn('user_id', $allowedUserIds)->count();
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

    // Get trainings for selected month (exclude cancelled and rejected)
    $trainings = Training::whereBetween('start_date', [$startOfMonth, $endOfMonth])
      ->whereNotIn('status', ['cancelled', 'rejected'])
      ->get();

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

  public function getChartOptionsProperty()
  {
    return [
      'chart' => [
        'type' => 'line',
        'height' => 350,
        'fontFamily' => 'inherit',
        'toolbar' => [
          'show' => false,
        ],
        'zoom' => [
          'enabled' => false,
        ],
        'dropShadow' => [
          'enabled' => true,
          'top' => 3,
          'left' => 0,
          'blur' => 4,
          'opacity' => 0.1,
        ],
      ],
      'series' => [
        [
          'name' => 'Training Count',
          'data' => $this->monthlyTrainingData,
        ],
      ],
      'stroke' => [
        'curve' => 'smooth',
        'width' => 3,
      ],
      'colors' => ['#6366f1'],
      'fill' => [
        'type' => 'gradient',
        'gradient' => [
          'shadeIntensity' => 1,
          'opacityFrom' => 0.4,
          'opacityTo' => 0.1,
          'stops' => [0, 90, 100],
        ],
      ],
      'xaxis' => [
        'categories' => $this->monthLabels,
        'labels' => [
          'style' => [
            'colors' => '#9ca3af',
            'fontSize' => '12px',
          ],
        ],
        'axisBorder' => [
          'show' => false,
        ],
        'axisTicks' => [
          'show' => false,
        ],
      ],
      'yaxis' => [
        'labels' => [
          'style' => [
            'colors' => '#9ca3af',
            'fontSize' => '12px',
          ],
          'formatter' => 'function(val) { return Math.floor(val); }',
        ],
        'min' => 0,
      ],
      'grid' => [
        'borderColor' => '#e5e7eb',
        'strokeDashArray' => 4,
        'xaxis' => [
          'lines' => [
            'show' => false,
          ],
        ],
      ],
      'markers' => [
        'size' => 5,
        'colors' => ['#6366f1'],
        'strokeColors' => '#fff',
        'strokeWidth' => 2,
        'hover' => [
          'size' => 8,
          'sizeOffset' => 3,
        ],
      ],
      'tooltip' => [
        'enabled' => true,
        'shared' => false,
        'intersect' => true,
        'theme' => 'light',
        'y' => [
          'formatter' => 'function(val) { return val + " Trainings"; }',
        ],
      ],
      'dataLabels' => [
        'enabled' => false,
      ],
    ];
  }

  public function render()
  {
    return view('pages.dashboard.leader-dashboard');
  }
}
