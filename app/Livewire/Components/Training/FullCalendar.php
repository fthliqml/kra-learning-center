<?php

namespace App\Livewire\Components\Training;

use App\Models\Training;
use App\Models\TrainingAssesment;
use App\Models\TrainingAttendance;
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
    public $modal = false;
    public $dayNumber = 1;
    public $attendances = [];
    public $employees = [];
    public $trainingId = 1;
    public $sessions = [];
    public $trainings = [];

    public function mount()
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
        $this->trainings = Training::with('sessions')->get();

        $training = Training::with(['sessions.attendances'])->find($this->trainingId);

        if (!$training)
            return;

        $this->employees = $training->assessments->map(fn($a) => $a->employee)->unique('id')->values();

        foreach ($training->sessions as $session) {
            foreach ($session->attendances as $attendance) {
                $this->attendances[$session->day_number][$attendance->employee_id] = [
                    'status' => $attendance->status,
                    'remark' => $attendance->notes,
                ];
            }
        }
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
        $this->selectedEvent = collect($this->trainings)->firstWhere('id', $eventId);
        $this->sessions = $this->selectedEvent->sessions;
        $this->modal = true;
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

        return collect($this->trainings) // sudah ada variabel trainings
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


    public function updateAttendance()
    {
        foreach ($this->employees as $employee) {
            $data = $this->attendances[$this->dayNumber][$employee->id] ?? null;

            if ($data) {
                TrainingAttendance::updateOrCreate(
                    [
                        'session_id' => $this->sessions[$this->dayNumber - 1]->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'status' => $data['status'],
                        'notes' => $data['remark'],
                    ]
                );
            }
        }

        $this->success('Berhasil memperbarui data!', position: 'toast-top toast-center');
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
