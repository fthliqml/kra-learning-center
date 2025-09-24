<?php

namespace App\Livewire\Components\Training;

use App\Models\Training;
use App\Models\Trainer;
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

    /** @var \Illuminate\Support\Collection<int,\App\Models\Training> */
    public $trainings;
    public array $days = [];

    protected $listeners = [
        'training-created' => 'refreshTrainings',
        'training-updated' => 'applyTrainingUpdate',
        'fullcalendar-open-event' => 'openEventModal',
    ];

    private array $trainingDetails = [];

    public function mount(): void
    {
        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
        $this->refreshTrainings();
    }

    public function setView(string $view): void
    {
        if (in_array($view, ['month', 'agenda']))
            $this->activeView = $view;
    }

    public function previousMonth(): void
    {
        if ($this->currentMonth === 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
        $this->recomputeDays();
        $this->stopGlobalOverlay();
    }

    public function nextMonth(): void
    {
        if ($this->currentMonth === 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
        $this->recomputeDays();
        $this->stopGlobalOverlay();
    }

    public function refreshTrainings(): void
    {
        [$start, $end] = $this->calendarRange();
        $this->trainings = Training::with('sessions')
            ->where(function ($q) use ($start, $end) {
                $q->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
            })->get();
        $this->recomputeDays();
        $this->trainingDetails = []; // reset cache
        $this->calendarVersion++;
        $this->stopGlobalOverlay();
    }

    public function applyTrainingUpdate($payload): void
    {
        if (!isset($payload['id']))
            return;
        foreach ($this->trainings as $t) {
            if ($t->id == $payload['id']) {
                foreach (['name', 'start_date', 'end_date'] as $f)
                    if (isset($payload[$f]))
                        $t->$f = $payload[$f];
                if (isset($payload['session']) && $t->relationLoaded('sessions')) {
                    $diff = $payload['session'];
                    foreach ($t->sessions as $sess) {
                        if ($sess->id == ($diff['id'] ?? null)) {
                            foreach (['room_name', 'room_location'] as $sf)
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
            $cache =& $this->trainingDetails[$id];
            foreach (['name', 'start_date', 'end_date'] as $f)
                if (isset($payload[$f]))
                    $cache[$f] = $payload[$f];
        }
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
        [$s, $e] = $this->calendarRange();
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

    private function trainingsForDate(string $iso): array
    {
        $c = Carbon::parse($iso);
        return $this->trainings->filter(fn($t) => $c->between(Carbon::parse($t->start_date), Carbon::parse($t->end_date)))
            ->values()->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'start_date' => $t->start_date,
                'end_date' => $t->end_date,
                'sessions' => $t->sessions,
            ])->toArray();
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
        $this->dispatch('open-detail-training-modal', $payload);
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
            'start_date' => $training->start_date,
            'end_date' => $training->end_date,
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

    private function stopGlobalOverlay(): void
    {
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('global-overlay-stop');
            return;
        }
        if (method_exists($this, 'dispatchBrowserEvent')) {
            $this->dispatchBrowserEvent('global-overlay-stop');
            return;
        }
    }
}
