<?php

namespace App\Livewire\Components\Certification;

use Livewire\Component;

class AgendaList extends Component
{
    /** @var array<int,array> */
    public array $days = [];

    public function open(int $sessionId): void
    {
        $this->dispatch('open-detail-certification-modal', $sessionId);
    }

    public function placeholder()
    {
        return view('components.skeletons.agenda-list', [
            'count' => 5,
        ]);
    }

    public function render()
    {
        return view('components.certification.agenda-list');
    }
}
