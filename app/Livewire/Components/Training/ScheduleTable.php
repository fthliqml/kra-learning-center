<?php

namespace App\Livewire\Components\Training;

use Livewire\Component;

class ScheduleTable extends Component
{
    public function headers()
    {
        return [
            ['key' => 'id', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'name', 'label' => 'Training Name', 'class' => 'w-[300px]'],
            ['key' => 'type', 'label' => 'Type', 'class' => '!text-center'],
            [
                'key' => 'date',
                'label' => 'Training Date',
                'class' => '!text-center',
            ],
            [
                'key' => 'frequency',
                'label' => 'Frequency',
                'class' => '!text-center',
                'format' => fn($row, $field) => $field . ' Days',
            ],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function render()
    {
        return view('components.training.schedule-table');
    }
}
