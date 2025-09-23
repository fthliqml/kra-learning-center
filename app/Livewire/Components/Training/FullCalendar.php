<?php

namespace App\Livewire\Components\Training;

use App\Models\Training;
use Livewire\Component;
use Carbon\Carbon;
use Mary\Traits\Toast;

class FullCalendar extends Component
{
    use Toast;
    public $currentMonth;
    public $currentYear;
    public $trainings = [];
    public $trainingDetails = []; // cache of detailed training payloads keyed by id

    protected $listeners = [
        'training-created' => 'refreshTrainings',
        'training-updated' => 'applyTrainingUpdate',
    ];

    public function refreshTrainings($payload = null)
    {
        [$startOfCalendar, $endOfCalendar] = $this->calendarRange();
        $this->trainings = Training::with('sessions')
            ->where(function ($q) use ($startOfCalendar, $endOfCalendar) {
                $q->whereDate('start_date', '<=', $endOfCalendar)
                    ->whereDate('end_date', '>=', $startOfCalendar);
            })
            ->get();
        // Clear detail cache for consistency
        $this->trainingDetails = [];
    }

    public function mount()
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
        $this->refreshTrainings();
    }

    public function previousMonth()
    {
        if ($this->currentMonth == 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
    }

    public function nextMonth()
    {
        if ($this->currentMonth == 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
    }

    public function openEventModal($eventId)
    {
        $payload = $this->loadTrainingDetail($eventId);
        if ($payload) {
            $this->dispatch('open-detail-training-modal', $payload);
        }
    }

    /**
     * Build & cache a normalized training detail payload with minimal fields.
     */
    private function loadTrainingDetail(int $id): ?array
    {
        if (isset($this->trainingDetails[$id])) {
            return $this->trainingDetails[$id];
        }

        $training = Training::with([
            'sessions.trainer.user',
            'sessions.attendances',
            'assessments.employee'
        ])->find($id);

        if (!$training) {
            return null;
        }

        // Collect employees (unique) from assessments
        $employees = $training->assessments
            ->map(fn($a) => $a->employee)
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn($e) => [
                'id' => $e->id,
                'NRP' => $e->nrp ?? null,
                'name' => $e->name,
                'section' => $e->section ?? null,
            ])->toArray();

        // Sessions normalized
        $sessions = $training->sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'day_number' => $s->day_number,
                'date' => $s->date,
                'room_name' => $s->room_name,
                'room_location' => $s->room_location,
                'trainer' => $s->trainer ? [
                    'id' => $s->trainer->id,
                    'name' => $s->trainer->name ?: ($s->trainer->user->name ?? null),
                ] : null,
                'attendances' => $s->attendances->map(fn($a) => [
                    'employee_id' => $a->employee_id,
                    'status' => $a->status,
                    'remark' => $a->notes,
                ])->toArray(),
            ];
        })->sortBy('day_number')->values()->toArray();

        $payload = [
            'id' => $training->id,
            'name' => $training->name,
            'start_date' => $training->start_date,
            'end_date' => $training->end_date,
            'sessions' => $sessions,
            'employees' => $employees,
        ];

        $this->trainingDetails[$id] = $payload;
        return $payload;
    }

    /**
     * Apply a minimal update payload to in-memory collections to avoid a full reload.
     */
    public function applyTrainingUpdate($payload)
    {
        if (!isset($payload['id'])) {
            return; // fallback: full refresh
        }
        // Update summary list
        foreach ($this->trainings as $t) {
            if ($t->id === $payload['id']) {
                if (isset($payload['name']))
                    $t->name = $payload['name'];
                if (isset($payload['start_date']))
                    $t->start_date = $payload['start_date'];
                if (isset($payload['end_date']))
                    $t->end_date = $payload['end_date'];
                // Patch single session trainer/room if diff provided
                if (isset($payload['session']) && $t->relationLoaded('sessions')) {
                    $diff = $payload['session'];
                    foreach ($t->sessions as $sess) {
                        if ((int) $sess->id === (int) ($diff['id'] ?? 0)) {
                            if (array_key_exists('room_name', $diff)) {
                                $sess->room_name = $diff['room_name'];
                            }
                            if (array_key_exists('room_location', $diff)) {
                                $sess->room_location = $diff['room_location'];
                            }
                            break;
                        }
                    }
                }
            }
        }
        // Update cache detail if present
        if (isset($this->trainingDetails[$payload['id']])) {
            $cache =& $this->trainingDetails[$payload['id']];
            foreach (['name', 'start_date', 'end_date'] as $field) {
                if (isset($payload[$field]))
                    $cache[$field] = $payload[$field];
            }
            if (isset($payload['session']) && isset($cache['sessions'])) {
                $diff = $payload['session'];
                foreach ($cache['sessions'] as &$sess) {
                    if ((int) $sess['id'] === (int) ($diff['id'] ?? 0)) {
                        foreach (['room_name', 'room_location', 'trainer'] as $f) {
                            if (array_key_exists($f, $diff)) {
                                $sess[$f] = $diff[$f];
                            }
                        }
                        break;
                    }
                }
                unset($sess);
            }
        }
    }

    private function calendarRange(): array
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $startOfCalendar = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);
        return [$startOfCalendar->toDateString(), $endOfCalendar->toDateString()];
    }

    public function assignTrainingLayers()
    {
        $sortedTrainings = collect($this->trainings)
            ->sortBy(['start_date', 'id'])
            ->values();

        $layerMapping = [];
        foreach ($sortedTrainings as $index => $training) {
            $layerMapping[$training['id']] = $index;
        }

        return $layerMapping;
    }

    public function getTrainingsForDate($date)
    {
        $layerMapping = $this->assignTrainingLayers();

        return collect($this->trainings)
            ->filter(function ($training) use ($date) {
                $startDate = Carbon::parse($training->start_date);
                $endDate = Carbon::parse($training->end_date);
                $currentDate = Carbon::parse($date);

                return $currentDate->between($startDate, $endDate);
            })
            ->map(function ($training) use ($date, $layerMapping) {
                $startDate = Carbon::parse($training->start_date);
                $endDate = Carbon::parse($training->end_date);
                $currentDate = Carbon::parse($date);

                return [
                    'id' => $training->id,
                    'name' => $training->name,
                    'start_date' => $training->start_date,
                    'end_date' => $training->end_date,
                    'sessions' => $training->sessions,
                    'isStart' => $currentDate->isSameDay($startDate),
                    'isEnd' => $currentDate->isSameDay($endDate),
                    'isBetween' => !$currentDate->isSameDay($startDate) && !$currentDate->isSameDay($endDate),
                    'layer' => $layerMapping[$training->id] ?? 0,
                ];
            })
            ->sortBy('layer')
            ->values()
            ->toArray();
    }

    public function openAddTrainingModal($date)
    {
        // Dispatch event to open add training modal
        $this->dispatch('open-add-training-modal', ['date' => $date]);
    }

    public function render()
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $startOfCalendar = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $current = $startOfCalendar->copy();

        while ($current <= $endOfCalendar) {
            $days[] = [
                'date' => $current->copy(),
                'isCurrentMonth' => $current->month === $this->currentMonth,
                'isToday' => $current->isToday(),
                'trainings' => $this->getTrainingsForDate($current->format('Y-m-d'))
            ];
            $current->addDay();
        }

        $monthName = $startOfMonth->format('F Y');

        return view('components.training.full-calendar', [
            'days' => $days,
            'monthName' => $monthName,
        ]);
    }

}
