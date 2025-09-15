<?php

namespace App\Livewire\Pages\Training;

use Livewire\Component;
use Carbon\Carbon;

class Schedule extends Component
{
    public $currentMonth;
    public $currentYear;
    public $selectedEvent = null;
    public $modal = false;

    // Sample training data
    public $trainings = [
        [
            'id' => 1,
            'title' => 'Training A',
            'location' => 'Wakatobi Room - EDC',
            'start_date' => '2025-09-06', // Mulai paling awal
            'end_date' => '2025-09-09',
            'description' => 'Advanced training program for team development',
            'instructor' => 'John Doe',
            'capacity' => 25,
            'registered' => 18
        ],
        [
            'id' => 2,
            'title' => 'Workshop X',
            'location' => 'Meeting Room 1',
            'start_date' => '2025-09-07', // Overlap dengan Training A, mulai setelah Training A
            'end_date' => '2025-09-10',
            'description' => 'Quick workshop session',
            'instructor' => 'Jane Smith',
            'capacity' => 15,
            'registered' => 10
        ],
        [
            'id' => 3,
            'title' => 'Seminar Y',
            'location' => 'Hall A',
            'start_date' => '2025-09-08', // Overlap dengan Training A dan Workshop X
            'end_date' => '2025-09-09',
            'description' => 'One day seminar',
            'instructor' => 'Bob Wilson',
            'capacity' => 50,
            'registered' => 35
        ],
        [
            'id' => 5,
            'title' => 'Seminar Y',
            'location' => 'Hall A',
            'start_date' => '2025-09-08', // Overlap dengan Training A dan Workshop X
            'end_date' => '2025-09-09',
            'description' => 'One day seminar',
            'instructor' => 'Bob Wilson',
            'capacity' => 50,
            'registered' => 35
        ],
        [
            'id' => 19,
            'title' => 'Seminar Y',
            'location' => 'Hall A',
            'start_date' => '2025-09-08', // Overlap dengan Training A dan Workshop X
            'end_date' => '2025-09-09',
            'description' => 'One day seminar',
            'instructor' => 'Bob Wilson',
            'capacity' => 50,
            'registered' => 35
        ],
        [
            'id' => 10,
            'title' => 'Seminar O',
            'location' => 'Hall A',
            'start_date' => '2025-09-08', // Overlap dengan Training A dan Workshop X
            'end_date' => '2025-09-09',
            'description' => 'One day seminar',
            'instructor' => 'Bob Wilson',
            'capacity' => 50,
            'registered' => 35
        ],
        [
            'id' => 11,
            'title' => 'Seminar Y',
            'location' => 'Hall A',
            'start_date' => '2025-09-08', // Overlap dengan Training A dan Workshop X
            'end_date' => '2025-09-09',
            'description' => 'One day seminar',
            'instructor' => 'Bob Wilson',
            'capacity' => 50,
            'registered' => 35
        ],
        [
            'id' => 4,
            'title' => 'Training D',
            'location' => 'Kakaban Room - EDC',
            'start_date' => '2025-09-13',
            'end_date' => '2025-09-16',
            'description' => 'Digital transformation workshop',
            'instructor' => 'Jane Smith',
            'capacity' => 30,
            'registered' => 22
        ]
    ];

    public array $rows = [
        [
            'nrp' => '50*****',
            'name' => 'Peserta 1',
            'section' => 'DYNO AND COMPLETION, SUB ASSY 1 AND 2',
        ],
        [
            'nrp' => '50*****',
            'name' => 'Peserta 2',
            'section' => 'ENGINE SHORT BLOCK LONG BLOCK AND MAIN ASSY',
        ],
    ];

    public function mount()
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
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

    public function hasMoreThanThreeEventsInAnyDay($days)
    {
        foreach ($days as $day) {
            if (count($day['trainings'] ?? []) > 3) {
                return true;
            }
        }
        return false;
    }

    public function getTrainingsForDate($date)
    {
        $layerMapping = $this->assignTrainingLayers();

        return collect($this->trainings)->filter(function ($training) use ($date) {
            $startDate = Carbon::parse($training['start_date']);
            $endDate = Carbon::parse($training['end_date']);
            $currentDate = Carbon::parse($date);

            return $currentDate->between($startDate, $endDate);
        })->map(function ($training) use ($date, $layerMapping) {
            $startDate = Carbon::parse($training['start_date']);
            $endDate = Carbon::parse($training['end_date']);
            $currentDate = Carbon::parse($date);

            return array_merge($training, [
                'isStart' => $currentDate->isSameDay($startDate),
                'isEnd' => $currentDate->isSameDay($endDate),
                'isBetween' => !$currentDate->isSameDay($startDate) && !$currentDate->isSameDay($endDate),
                'layer' => $layerMapping[$training['id']] ?? 0
            ]);
        })
            ->sortBy('layer')
            ->values()
            ->toArray();
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
        $hasScrollableDay = $this->hasMoreThanThreeEventsInAnyDay($days);

        return view('livewire.pages.training.schedule', [
            'days' => $days,
            'monthName' => $monthName,
            'hasScrollableDay' => $hasScrollableDay
        ]);
    }

}
