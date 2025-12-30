<?php

namespace App\Livewire\Pages\dashboard;

use App\Models\Certification;
use App\Models\Request;
use App\Models\Training;
use App\Models\TrainingPlan;
use App\Models\SelfLearningPlan;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class leaderDashboard extends Component
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

    public function loadCalendarEvents()
    {
        $this->calendarEvents = [];

        // Get trainings for current month and next 2 months
        $startDate = now()->startOfMonth();
        $endDate = now()->addMonths(2)->endOfMonth();

        $userId = Auth::id();
        $user = Auth::user();

        // Leader/Instructor sees trainings where they are the trainer
        if (in_array($user->role ?? null, ['leader', 'instructor'], true)) {
            // Get trainer record for current user
            $trainer = \App\Models\Trainer::where('user_id', $userId)->first();

            if ($trainer) {
                $trainings = Training::with(['sessions.trainer.user'])
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->whereHas('sessions', function ($q) use ($trainer) {
                        $q->where('trainer_id', $trainer->id);
                    })
                    ->get();
            } else {
                $trainings = collect();
            }
        }
        // Employee sees trainings where they are a participant
        elseif (($user->role ?? null) === 'employee') {
            $trainings = Training::with(['sessions.trainer.user'])
                ->whereBetween('start_date', [$startDate, $endDate])
                ->whereHas('assessments', function ($q) use ($userId) {
                    $q->where('employee_id', $userId);
                })
                ->get();
        }
        // Fallback: show all trainings
        else {
            $trainings = Training::with(['sessions.trainer.user'])
                ->whereBetween('start_date', [$startDate, $endDate])
                ->get();
        }

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

        // Leader sees all certification schedules
        $certifications = Certification::with(['sessions'])
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

        // Pending approvals (combined: certification approval + training approval + training request + IDP approval)
        $pendingCertifications = Certification::where('status', 'pending')->count();
        $pendingTrainings = Training::where('status', 'pending')->count();
        $pendingRequests = Request::where('status', 'pending')->count();

        // IDP (Individual Development Plan) pending approvals for leaders
        $pendingIdp = TrainingPlan::where('status', 'pending_leader')->count()
            + SelfLearningPlan::where('status', 'pending_leader')->count()
            + MentoringPlan::where('status', 'pending_leader')->count()
            + ProjectPlan::where('status', 'pending_leader')->count();

        $this->pendingApprovals = $pendingCertifications + $pendingTrainings + $pendingRequests + $pendingIdp;
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
