<?php

namespace App\Livewire\Components\TrainingSchedule;

use App\Models\Training;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Parent orchestrator: memegang state bulan, daftar trainings ringan (sessions eager),
 * compute struktur days untuk dipass ke FullCalendar & AgendaList.
 */
class ScheduleView extends Component
{
    public string $activeView = 'month';
    public int $currentMonth;
    public int $currentYear;
    public int $calendarVersion = 0;
    /** Counts of trainings per month for the current year (1..12) */
    public array $monthlyTrainingCounts = [];
    /** Counts for navigation badges */
    public int $prevMonthCount = 0;
    public int $nextMonthCount = 0;
    public int $currentMonthCount = 0;


    // Filters
    public $filterTrainerId = null;
    public $filterType = null;

    public ?string $filterTrainerName = null;

    protected $listeners = [
        // Refresh trainings when create/update/delete happens to ensure UI consistency
        'training-created' => 'refreshTrainings',
        'training-updated' => 'refreshTrainings',
        'training-deleted' => 'removeTraining',
        'training-closed' => 'onTrainingClosed',
        
        // Modal interactions
        'fullcalendar-open-event' => 'openEventModal',
        'training-info-updated' => 'onTrainingInfoUpdated',
        'schedule-filters-updated' => 'onFiltersUpdated',
    ];

    private array $trainingDetails = [];
    

    // In-memory cache for count queries to prevent redundant DB calls during a single request cycle
    private array $yearCountsCache = [];
    private array $monthCountsCache = [];

    public function mount(): void
    {
        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
        $this->refreshTrainings();
    }

    public function setView(string $view): void
    {
        if (!in_array($view, ['month', 'agenda'])) {
            return;
        }
        if ($this->activeView === $view) {
            return; // no change
        }
        $this->activeView = $view;
        // No manual refresh needed, render() handles it
    }

    public function previousMonth(): void
    {
        if ($this->currentMonth === 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
        // Re-query trainings for the new visible month so data is up-to-date
        $this->refreshTrainings();
    }

    public function nextMonth(): void
    {
        if ($this->currentMonth === 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
        // Re-query trainings for the new visible month so data is up-to-date
        $this->refreshTrainings();
    }

    public function setMonth(int $month): void
    {
        if ($month < 1 || $month > 12)
            return;
        $this->currentMonth = $month;
        $this->refreshTrainings();
    }

    public function refreshTrainings(): void
    {
        // Indicate that data is stale by clearing internal caches.
        // The actual fresh data fetching happens on-demand within the render() -> computeDays() flow.
        $this->yearCountsCache = [];
        $this->monthCountsCache = [];
        $this->trainingDetails = []; // reset detail cache
        $this->calendarVersion++;
        
        // Broadcast context update to other components (e.g. Navigation)
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('schedule-month-context', year: $this->currentYear, month: $this->currentMonth);
        }
    }

    public function applyTrainingUpdate($payload): void
    {
        if (!isset($payload['id'])) return;
        
        // Just invalidate caches and force re-render.
        // We don't need complex in-memory updates anymore because fetch is on-demand during render.
        $this->refreshTrainings();
    }

    public function onTrainingInfoUpdated(...$args): void
    {
        $payload = $args[0] ?? [];
        if (!is_array($payload) || !isset($payload['id'])) return;
        $this->applyTrainingUpdate($payload);
    }

    public function onTrainingClosed($payload = null): void
    {
        if (!$payload || !isset($payload['id'])) return;
        $this->refreshTrainings();
    }

    /**
     * Build counts per month for current year and compute prev/next/current counts for navigation badges.
     */
    private function computeMonthNavCounts(): void
    {
        $this->monthlyTrainingCounts = $this->buildYearCounts($this->currentYear);
        $this->currentMonthCount = $this->monthlyTrainingCounts[$this->currentMonth] ?? 0;

        // Previous month (handle year wrap)
        $prevMonth = $this->currentMonth === 1 ? 12 : $this->currentMonth - 1;
        $prevYear = $this->currentMonth === 1 ? $this->currentYear - 1 : $this->currentYear;
        $this->prevMonthCount = $prevYear === $this->currentYear
            ? ($this->monthlyTrainingCounts[$prevMonth] ?? 0)
            : $this->countForMonth($prevYear, $prevMonth);

        // Next month (handle year wrap)
        $nextMonth = $this->currentMonth === 12 ? 1 : $this->currentMonth + 1;
        $nextYear = $this->currentMonth === 12 ? $this->currentYear + 1 : $this->currentYear;
        $this->nextMonthCount = $nextYear === $this->currentYear
            ? ($this->monthlyTrainingCounts[$nextMonth] ?? 0)
            : $this->countForMonth($nextYear, $nextMonth);
    }

    /**
     * Count trainings per month for a given year. Each training counts once per overlapped month.
     */
    private function buildYearCounts(int $year): array
    {
        // Check cache first
        if (isset($this->yearCountsCache[$year])) {
            return $this->yearCountsCache[$year];
        }

        /** @var \App\Models\User|null $user */
        $counts = array_fill(1, 12, 0);
        $yearStart = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::createFromDate($year, 12, 31)->endOfDay();

        $trainingsQuery = Training::select('id', 'start_date', 'end_date')
            ->whereDate('start_date', '<=', $yearEnd)
            ->whereDate('end_date', '>=', $yearStart);
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && !$user->hasRole('admin')) {
            // Apply role-based visibility: generic users only see trainings they are involved in
            // either as an attendee (assessment) or as a trainer.
            $trainingsQuery->where(function ($q) use ($user) {
                $q->whereHas('assessments', function ($qq) use ($user) {
                    $qq->where('employee_id', $user->id);
                })->orWhereHas('sessions.trainer.user', function ($qq) use ($user) {
                    $qq->where('users.id', $user->id);
                });
            });
        }
        $trainings = $trainingsQuery->get();

        foreach ($trainings as $t) {
            $ts = Carbon::parse($t->start_date);
            $te = Carbon::parse($t->end_date);
            $startMonth = $ts->year < $year ? 1 : $ts->month;
            $endMonth = $te->year > $year ? 12 : $te->month;
            for ($m = max(1, $startMonth); $m <= min(12, $endMonth); $m++) {
                $counts[$m]++;
            }
        }
        
        // Cache the result
        $this->yearCountsCache[$year] = $counts;
        
        return $counts;
    }

    /**
     * Query trainings for a specific date range.
     * Used by computeDays() in render().
     */
    private function queryTrainingsForRange(Carbon $start, Carbon $end)
    {
        // ... (Using logic from old refreshTrainings) ...
        $query = Training::with(['sessions.trainer.user', 'competency', 'module.competency', 'course.competency'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date');

        if ($this->filterTrainerId) {
            $query->whereHas('sessions', fn($q) => $q->where('trainer_id', $this->filterTrainerId));
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && !$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('assessments', function ($qq) use ($user) {
                    $qq->where('employee_id', $user->id);
                })->orWhereHas('sessions.trainer.user', function ($qq) use ($user) {
                    $qq->where('users.id', $user->id);
                });
            });
        }
        
        return $query->get();
    }
    
    /**
     * Count trainings that overlap a specific month/year. Each training counted once if overlapping.
     */
    private function countForMonth(int $year, int $month): int
    {
        $cacheKey = "{$year}-{$month}";
        
        // Check cache first
        if (isset($this->monthCountsCache[$cacheKey])) {
            return $this->monthCountsCache[$cacheKey];
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();
        $query = Training::whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && !$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('assessments', function ($qq) use ($user) {
                    $qq->where('employee_id', $user->id);
                })->orWhereHas('sessions.trainer.user', function ($qq) use ($user) {
                    $qq->where('users.id', $user->id);
                });
            });
        }
        $count = $query->distinct('id')->count('id');
        
        // Cache the result
        $this->monthCountsCache[$cacheKey] = $count;
        
        return $count;
    }

    public function openAdd(string $date): void
    {
        $this->dispatch('open-add-training-modal', ['date' => $date]);
    }

    public function openEventModal(int $id, string $clickedDate = null): void
    {
        $payload = $this->loadTrainingDetail($id);
        if (!$payload)
            return;
        if ($clickedDate) {
            $start = Carbon::parse($payload['start_date']);
            $target = Carbon::parse($clickedDate);
            if ($target->between($start, Carbon::parse($payload['end_date']))) {
                $payload['initial_day_number'] = $start->diffInDays($target) + 1;
            }
        }
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->hasRole('admin')) {
            // Check if training is closed, approved, or rejected
            $status = strtolower($payload['status'] ?? '');
            $isClosed = in_array($status, ['done', 'approved', 'rejected']);

            // Build actions based on training status
            $actions = [
                [
                    'label' => 'View Detail',
                    'event' => 'open-detail-training-modal',
                    'variant' => 'outline'
                ]
            ];

            // Only allow edit if training is not closed, approved, or rejected
            if (!$isClosed) {
                $actions[] = [
                    'label' => 'Edit Training',
                    'event' => 'open-training-form-edit',
                    'variant' => 'primary'
                ];
            }

            $this->dispatch('open-action-choice', [
                'title' => 'Training Action',
                'message' => 'What would you like to do with this training?',
                'payload' => $payload,
                'actions' => $actions
            ]);
        } else {
            $this->dispatch('open-detail-training-modal', $payload);
        }
    }

    public function removeTraining($payload): void
    {
        if (!isset($payload['id']))
            return;
        $id = $payload['id'];
        
        // Clear cached detail
        unset($this->trainingDetails[$id]);
        
        // Refresh trainings data
        $this->refreshTrainings();
    }

    private function loadTrainingDetail(int $id): ?array
    {
        if (isset($this->trainingDetails[$id]))
            return $this->trainingDetails[$id];
        $training = Training::with(['sessions.trainer.user', 'sessions.attendances', 'assessments.employee'])->find($id);
        if (!$training)
            return null;
        $employees = $training->assessments->map(fn($a) => $a->employee)->filter()->unique('id')->values()
            ->map(fn($e) => [
                'id' => $e->id,
                'NRP' => $e->nrp ?? $e->NRP ?? null,
                'name' => $e->name,
                'section' => $e->section ?? null,
            ])->toArray();
        $sessions = $training->sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'day_number' => $s->day_number,
                'date' => $s->date,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'room_name' => $s->room_name,
                'room_location' => $s->room_location,
                'trainer' => $s->trainer ? ['id' => $s->trainer->id, 'name' => $s->trainer->name ?? ($s->trainer->user->name ?? null)] : null,
                'attendances' => $s->attendances->map(fn($a) => [
                    'id' => $a->id,
                    'employee_id' => $a->employee_id,
                    'status' => $a->status,
                    'remark' => $a->notes ?? $a->remark ?? null,
                ])->toArray(),
            ];
        })->sortBy('day_number')->values()->toArray();
        $payload = [
            'id' => $training->id,
            'name' => $training->name,
            'group_comp' => $training->group_comp,
            'start_date' => $training->start_date,
            'end_date' => $training->end_date,
            'type' => $training->type, // include type so detail modal badge can render without extra query
            'status' => $training->status, // include status so modal can hide close action when done
            'sessions' => $sessions,
            'employees' => $employees,
        ];
        $this->trainingDetails[$id] = $payload;
        return $payload;
    }

    /**
     * Compute days structure for the current month
     */
    private function computeDays(): array
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfWeek(Carbon::MONDAY);
        $endOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Fetch trainings for the visible range
        $trainings = $this->queryTrainingsForRange($startOfMonth, $endOfMonth);
        
        // Cache trainings locally for this request
        $this->_trainingsCache = $trainings;

        $days = [];
        $current = $startOfMonth->copy();

        while ($current <= $endOfMonth) {
            $iso = $current->toDateString();
            $dayTrainings = $this->getTrainingsForDate($trainings, $iso);
            
            $days[] = [
                'date' => $current->copy(),
                'isCurrentMonth' => $current->month === $this->currentMonth,
                'isToday' => $current->isToday(),
                'trainings' => $dayTrainings,
            ];
            $current->addDay();
        }
        
        return $days;
    }
    
    /**
     * Helper to filter trainings/sessions for a specific date from the collection
     */
    private function getTrainingsForDate($trainings, string $iso): array
    {
        $c = Carbon::parse($iso);
        return $trainings->filter(fn($t) => $c->between(Carbon::parse($t->start_date), Carbon::parse($t->end_date)))
            ->values()->map(function ($t) use ($iso) {
                $status = strtolower($t->status ?? '');
                $isClosed = in_array($status, ['done', 'approved', 'rejected']);
                $isPast = Carbon::parse($iso)->endOfDay()->isPast();

                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'group_comp' => $t->group_comp,
                    'type' => $t->type ?? null,
                    'status' => $t->status ?? null,
                    'start_date' => $t->start_date,
                    'end_date' => $t->end_date,
                    'sessions' => $t->sessions, // sessions are already eager loaded and filtered if possible (though we eager loaded all)
                    'is_closed' => $isClosed,
                    'is_past' => $isPast,
                    'is_faded' => $isClosed,
                ];
            })->toArray();
    }

    public function getMonthNameProperty(): string
    {
        return Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    public function render()
    {
        // Re-compute counts and days on every render to ensure freshness without bloated state
        $this->computeMonthNavCounts();
        $days = $this->computeDays();
        
        return view('components.training-schedule.schedule-view', [
            'days' => $days
        ]);
    }
    
    // Internal cache for the current request cycle
    protected $_trainingsCache;

    public function onFiltersUpdated($trainerId = null, $type = null): void
    {
        // Normalize incoming filter values from Livewire event
        $this->filterTrainerId = $trainerId ?: null;
        $this->filterType = $type ?: null;
        
        // Pre-fetch trainer name for display purposes (avoids N+1 in view)
        if ($this->filterTrainerId) {
            $trainer = Trainer::with('user')->find($this->filterTrainerId);
            $this->filterTrainerName = $trainer?->name ?: $trainer?->user?->name ?? 'ID ' . $this->filterTrainerId;
        } else {
            $this->filterTrainerName = null;
        }
        
        $this->refreshTrainings();
    }
}
