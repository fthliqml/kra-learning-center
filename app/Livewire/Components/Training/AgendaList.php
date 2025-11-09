<?php

namespace App\Livewire\Components\Training;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgendaList extends Component
{
  /** @var array<int,array> */
  public array $days = [];

  public function getItemsProperty(): Collection
  {
    return collect($this->days)
      ->map(fn($d) => collect($d['trainings'])->map(fn($t) => [
        'date' => $d['date'],
        'iso' => $d['date']->format('Y-m-d'),
        'isToday' => $d['isToday'],
        'name' => $t['name'],
        'id' => $t['id'],
        'type' => $t['type'] ?? null,
        'status' => $t['status'] ?? null,
        'sessions' => $t['sessions'],
      ]))
      ->flatten(1)
      ->sortBy(fn($x) => $x['date'])
      ->values();
  }

  public function open(int $id, string $iso): void
  {
    $this->dispatch('fullcalendar-open-event', id: $id, clickedDate: $iso);
  }

  public function placeholder()
  {
    // Use a dedicated Blade partial so Blade directives (loops) are compiled.
    return view('components.skeletons.agenda-list', [
      'count' => 5,
    ]);
  }

  public function render()
  {
    return view('components.training.agenda-list');
  }
}

