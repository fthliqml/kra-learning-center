<?php

namespace App\Livewire\Components\TrainingSchedule;

use Livewire\Component;

/** Presentational month grid (state dipegang ScheduleView) */
class FullCalendar extends Component
{
    /** @var array<int,array{date:mixed,isCurrentMonth:bool,isToday:bool,trainings:array}> */
    public array $days = [];
    public string $monthName = '';

    public function openAdd(string $date): void
    {
        $this->dispatch('open-add-training-modal', ['date' => $date]);
    }

    public function openTraining(int $id, string $date = null): void
    {
        $this->dispatch('fullcalendar-open-event', id: $id, clickedDate: $date);
    }

    public function placeholder()
    {
        return view('components.skeletons.full-calendar');
    }

    public function render()
    {
        return view('components.training-schedule.full-calendar');
    }
}

