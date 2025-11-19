<?php

namespace App\Livewire\Components\Certification;

use Livewire\Component;

class MonthGrid extends Component
{
    /** @var array<int,array{date:mixed,isCurrentMonth:bool,isToday:bool,sessions:array}> */
    public array $days = [];
    public string $monthName = '';

    public function open(int $sessionId): void
    {
        $this->dispatch('open-detail-certification-modal', $sessionId);
    }

    public function placeholder()
    {
        return view('components.skeletons.full-calendar');
    }

    public function render()
    {
        return view('components.certification.month-grid');
    }
}
