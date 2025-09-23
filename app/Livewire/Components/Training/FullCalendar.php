<?php

namespace App\Livewire\Components\Training;

use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use App\Models\User;
use Livewire\Component;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Mary\Traits\Toast;

class FullCalendar extends Component
{
    use Toast;
    public $currentMonth;
    public $currentYear;
    public $selectedEvent = null;
    public $trainingDateRange = null;
    public $modal = false;
    public $dayNumber = 1;
    public $attendances = [];
    public $employees = [];
    public $sessions = [];
    public $trainings = [];
    public $trainer = [];
    public $trainers = [];
    public $editModes = [
        'training_name' => false,
        'date' => false,
        'location' => false,
        'trainer' => false
    ];


    public function mount()
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
        $this->trainings = Training::with('sessions')->get();
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
        $this->resetEditModes();


        $this->trainers = Trainer::with('user')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name ?: ($t->user->name ?? null), // unified display name
                'user' => [
                    'name' => $t->user->name ?? null,
                ],
            ])->toArray();

        // Retrieve complete training data
        $trainings = Training::with(['sessions.attendances', 'sessions.trainer.user', 'assessments.employee'])
            ->find($eventId);

        if (!$trainings) {
            return;
        }

        // Get employees list from assessments
        $this->employees = $trainings->assessments
            ->map(fn($a) => $a->employee)
            ->unique('id')
            ->values();

        // Map attendance per session & employee
        foreach ($trainings->sessions as $session) {
            foreach ($session->attendances as $attendance) {
                $this->attendances[$session->day_number][$attendance->employee_id] = [
                    'status' => $attendance->status,
                    'remark' => $attendance->notes,
                ];
            }
        }

        // Get event from the previously loaded trainings list
        $event = collect($this->trainings)->firstWhere('id', $eventId);

        if ($event) {
            $this->sessions = $event->sessions->toArray();
            $this->selectedEvent = $event->toArray();
            $sessionTrainer = $event->sessions[$this->dayNumber - 1]->trainer ?? null;
            $this->trainer = [
                'id' => $sessionTrainer?->id,
                'name' => $sessionTrainer?->name ?: ($sessionTrainer?->user?->name ?? null),
            ];
        }

        $this->modal = true;
    }


    public function resetEditModes()
    {
        $this->editModes = array_map(fn($v) => false, $this->editModes);
    }

    public function closeModal()
    {
        $this->modal = false;
        $this->selectedEvent = null;
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

    public function getCurrentSessionProperty()
    {
        return $this->sessions[(int) $this->dayNumber - 1] ?? null;
    }

    private function parseDateRange($dateRange): array
    {
        $dates = explode(' to ', $dateRange);

        $start = $dates[0] ?? null;
        $end = $dates[1] ?? $dates[0] ?? null;

        return [
            'start' => $start ? Carbon::parse($start)->format('Y-m-d') : null,
            'end' => $end ? Carbon::parse($end)->format('Y-m-d') : null,
        ];
    }

    public function updatedDayNumber()
    {
        $event = Training::with('sessions.trainer.user')->find($this->selectedEvent['id']);

        if ($event && isset($event->sessions[$this->dayNumber - 1])) {
            $sessionTrainer = $event->sessions[$this->dayNumber - 1]->trainer;
            $this->trainer = [
                'id' => $sessionTrainer?->id,
                'name' => $sessionTrainer?->name ?: ($sessionTrainer?->user?->name ?? null),
            ];
        }

    }

    public function updatedTrainerId($value)
    {
        $found = collect($this->trainers)->firstWhere('id', (int) $value);
        if ($found) {
            $this->trainer = [
                'id' => $found['id'],
                'name' => $found['name'],
            ];
        } else {
            $this->trainer = ['id' => null, 'name' => null];
        }
    }

    public function updateAttendance()
    {
        $dates = $this->parseDateRange($this->trainingDateRange);


        $startDate = $dates['start'];
        $endDate = $dates['end'];

        $updateData = [
            'name' => $this->selectedEvent['name'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        // remove keys with null value
        $updateData = array_filter($updateData, fn($value) => !is_null($value));

        Training::where('id', $this->selectedEvent['id'])
            ->update($updateData);

        if ($this->currentSession) {
            TrainingSession::where('id', $this->currentSession['id'])->update([
                'room_name' => $this->currentSession['room_name'] ?? null,
                'room_location' => $this->currentSession['room_location'] ?? null,
                'trainer_id' => $this->trainer['id'] ?? null,
            ]);
        }

        foreach ($this->employees as $employee) {
            $data = $this->attendances[$this->dayNumber][$employee->id] ?? null;

            if ($data) {
                TrainingAttendance::updateOrCreate(
                    [
                        'session_id' => $this->sessions[$this->dayNumber - 1]['id'],
                        'employee_id' => $employee->id,
                    ],
                    [
                        'status' => $data['status'],
                        'notes' => $data['remark'],
                    ]
                );
            }
        }

        $this->trainings = Training::with('sessions')->get();

        $this->modal = false;

        $this->success('Successfully updated data!', position: 'toast-top toast-center');
    }

    public function trainingDates()
    {
        $training = Training::find(1);

        if (!$training) {
            return collect();
        }

        $period = CarbonPeriod::create($training->start_date, $training->end_date);

        return collect($period)->map(function ($date, $index) {
            $formatted = $date->format('d M Y');
            return [
                'id' => $index + 1,
                'name' => $formatted,
            ];
        })->values();
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
            'trainingDates' => $this->trainingDates()
        ]);
    }

}
