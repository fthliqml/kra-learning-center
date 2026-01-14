<?php

namespace App\Livewire\Components\Training;

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

    /** @var \Illuminate\Support\Collection<int,\App\Models\Training> */
    public $trainings;
    public array $days = [];
    // Filters
    public $filterTrainerId = null;
    public $filterType = null;
    // PERFORMANCE: Store trainer name to avoid query in Blade
    public ?string $filterTrainerName = null;

    protected $listeners = [
        'training-created' => 'refreshTrainings',
        // Unified: any update triggers full refresh to keep sessions & counts consistent
        'training-updated' => 'refreshTrainings',
        'training-deleted' => 'removeTraining',
        'training-closed' => 'onTrainingClosed',
        'fullcalendar-open-event' => 'openEventModal',
        'training-info-updated' => 'onTrainingInfoUpdated',
        'schedule-filters-updated' => 'onFiltersUpdated',
    ];

    private array $trainingDetails = [];
    
    // PERFORMANCE: Cache for year/month counts to avoid repeated queries
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
        $this->refreshTrainings(); // refetch sesuai view baru
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
        // PERFORMANCE: Clear counts cache when trainings change
        // This ensures fresh data after create/update/delete
        $this->yearCountsCache = [];
        $this->monthCountsCache = [];
        
        // Pilih query sesuai view
        $this->trainings = $this->activeView === 'agenda'
            ? $this->fetchAgendaTrainings()
            : $this->fetchMonthTrainings();
        $this->recomputeDays();
        $this->computeMonthNavCounts();
        $this->trainingDetails = []; // reset cache
        $this->calendarVersion++;
        // Broadcast current month context so other components (e.g., TrainingFormModal) can align default datepicker month
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('schedule-month-context', year: $this->currentYear, month: $this->currentMonth);
        }
    }

    public function applyTrainingUpdate($payload): void
    {
        if (!isset($payload['id']))
            return;
        foreach ($this->trainings as $t) {
            if ($t->id == $payload['id']) {
                // If date range changed, perform a full refresh to pull newly created sessions
                $dateChanged = false;
                $origStart = $t->start_date;
                $origEnd = $t->end_date;
                foreach (['name', 'start_date', 'end_date'] as $f)
                    if (isset($payload[$f]))
                        $t->$f = $payload[$f];
                if (isset($payload['start_date']) && $payload['start_date'] !== $origStart)
                    $dateChanged = true;
                if (isset($payload['end_date']) && $payload['end_date'] !== $origEnd)
                    $dateChanged = true;
                if ($dateChanged) {
                    // Recompute and re-query trainings so sessions include new days
                    $this->refreshTrainings();
                    return;
                }
                if (isset($payload['session']) && $t->relationLoaded('sessions')) {
                    $diff = $payload['session'];
                    foreach ($t->sessions as $sess) {
                        if ($sess->id == ($diff['id'] ?? null)) {
                            foreach (['room_name', 'room_location', 'start_time', 'end_time'] as $sf)
                                if (array_key_exists($sf, $diff))
                                    $sess->$sf = $diff[$sf];
                            // Update in-memory trainer relation so UI reflects instantly
                            if (array_key_exists('trainer', $diff)) {
                                $newTrainer = $diff['trainer'];
                                if ($newTrainer && isset($newTrainer['id'])) {
                                    $trainerModel = new Trainer([
                                        'id' => $newTrainer['id'],
                                        'name' => $newTrainer['name'] ?? null,
                                    ]);
                                    $sess->setRelation('trainer', $trainerModel);
                                } else {
                                    $sess->unsetRelation('trainer');
                                }
                            }
                            break;
                        }
                    }
                }
                break;
            }
        }
        $this->recomputeDays();
        // patch cache detail
        $id = $payload['id'];
        if (isset($this->trainingDetails[$id])) {
            $cache = &$this->trainingDetails[$id];
            foreach (['name', 'start_date', 'end_date'] as $f)
                if (isset($payload[$f]))
                    $cache[$f] = $payload[$f];
            if (isset($payload['session']) && isset($cache['sessions']) && is_array($cache['sessions'])) {
                foreach ($cache['sessions'] as &$cs) {
                    if (($cs['id'] ?? null) == ($payload['session']['id'] ?? null)) {
                        foreach (['room_name', 'room_location', 'start_time', 'end_time'] as $sf) {
                            if (array_key_exists($sf, $payload['session'])) {
                                $cs[$sf] = $payload['session'][$sf];
                            }
                        }
                        if (array_key_exists('trainer', $payload['session'])) {
                            $cs['trainer'] = $payload['session']['trainer'] ?? null;
                        }
                        break;
                    }
                }
                unset($cs);
            }
        }
        $this->calendarVersion++;
    }

    public function onTrainingInfoUpdated(...$args): void
    {
        $payload = $args[0] ?? [];
        if (!is_array($payload) || !isset($payload['id'])) {
            return;
        }
        // If date range changed we rely on existing logic in applyTrainingUpdate
        $this->applyTrainingUpdate($payload);
    }

    public function onTrainingClosed($payload = null): void
    {
        if (!$payload || !isset($payload['id'])) {
            return;
        }

        // Update training status in memory
        $id = $payload['id'];
        $newStatus = $payload['status'] ?? 'done'; // Allow status to be passed, default to 'done'

        foreach ($this->trainings as $t) {
            if ($t->id == $id) {
                $t->status = $newStatus;
                break;
            }
        }

        // Update cached detail if exists
        if (isset($this->trainingDetails[$id])) {
            $this->trainingDetails[$id]['status'] = $newStatus;
        }

        // Recompute days to reflect status change in UI
        $this->recomputeDays();
        $this->calendarVersion++;
    }

    private function calendarRange(): array
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        return [
            $startOfMonth->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()
        ];
    }

    private function recomputeDays(): void
    {
        if ($this->activeView === 'agenda') {
            [$s, $e] = $this->strictMonthRange();
        } else {
            [$s, $e] = $this->calendarRange();
        }
        $start = Carbon::parse($s);
        $end = Carbon::parse($e);
        $days = [];
        $cur = $start->copy();
        while ($cur <= $end) {
            $iso = $cur->format('Y-m-d');
            $days[] = [
                'date' => $cur->copy(),
                'isCurrentMonth' => $cur->month === $this->currentMonth,
                'isToday' => $cur->isToday(),
                'trainings' => $this->trainingsForDate($iso),
            ];
            $cur->addDay();
        }
        $this->days = $days;
    }

    /**
     * Strict range hanya tanggal di dalam bulan aktif (tanpa padding minggu).
     */
    private function strictMonthRange(): array
    {
        $start = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * Query data untuk month view (menggunakan calendarRange extended full weeks)
     */
    private function fetchMonthTrainings()
    {
        [$start, $end] = $this->calendarRange();
        // PERFORMANCE: Eager load all relations needed in calendar view to avoid N+1
        $query = Training::with([
            'sessions.trainer.user',
            'competency:id,type',
            'module.competency:id,type',
            'course.competency:id,type'
        ])
            ->where(function ($q) use ($start, $end) {
                $q->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
            });
        // Apply filters
        if ($this->filterType && in_array($this->filterType, ['LMS', 'IN', 'OUT', 'BLENDED'])) {
            $query->where('type', $this->filterType);
        }
        if ($this->filterTrainerId) {
            $query->whereHas('sessions', function ($q) {
                $q->where('trainer_id', $this->filterTrainerId);
            });
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
     * Query data untuk agenda view (strict bulan saja)
     */
    private function fetchAgendaTrainings()
    {
        [$start, $end] = $this->strictMonthRange();
        // PERFORMANCE: Eager load all relations needed in agenda view to avoid N+1
        $query = Training::with([
            'sessions.trainer.user',
            'competency:id,type',
            'module.competency:id,type',
            'course.competency:id,type'
        ])
            ->where(function ($q) use ($start, $end) {
                $q->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
            });
        if ($this->filterType) {
            if ($this->filterType && in_array($this->filterType, ['LMS', 'IN', 'OUT', 'BLENDED'])) {
                $query->where('type', $this->filterType);
            }
        }
        if ($this->filterTrainerId) {
            $query->whereHas('sessions', function ($q) {
                $q->where('trainer_id', $this->filterTrainerId);
            });
        }
        /** @var User|null $user */    $user = Auth::user();
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
     * PERFORMANCE: Results are cached per year.
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
     * Count trainings that overlap a specific month/year. Each training counted once if overlapping.
     * PERFORMANCE: Results are cached per year-month.
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

    private function trainingsForDate(string $iso): array
    {
        $c = Carbon::parse($iso);
        return $this->trainings->filter(fn($t) => $c->between(Carbon::parse($t->start_date), Carbon::parse($t->end_date)))
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
                    'sessions' => $t->sessions,
                    'is_closed' => $isClosed,
                    'is_past' => $isPast,
                    // Fade only when training is closed; do not fade merely because date has passed.
                    'is_faded' => $isClosed,
                ];
            })->toArray();
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
        if ($this->trainings) {
            $this->trainings = $this->trainings->reject(fn($t) => $t->id == $id)->values();
        }
        // clear cached detail
        unset($this->trainingDetails[$id]);
        $this->recomputeDays();
        $this->calendarVersion++;
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

    public function getMonthNameProperty(): string
    {
        return Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    public function render()
    {
        return view('components.training.schedule-view');
    }

    public function onFiltersUpdated($trainerId = null, $type = null): void
    {
        // normalize incoming event (Livewire passes named args as separate parameters)
        $this->filterTrainerId = $trainerId ?: null;
        $this->filterType = $type ?: null;
        
        // PERFORMANCE: Resolve trainer name once here, not in Blade
        if ($this->filterTrainerId) {
            $trainer = Trainer::with('user')->find($this->filterTrainerId);
            $this->filterTrainerName = $trainer?->name ?: $trainer?->user?->name ?? 'ID ' . $this->filterTrainerId;
        } else {
            $this->filterTrainerName = null;
        }
        
        $this->refreshTrainings();
    }
}
