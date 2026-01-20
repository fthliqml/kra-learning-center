<?php

namespace App\Livewire\Components\TrainingSchedule;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgendaList extends Component
{
    /** @var array<int,array> */
    public array $days = [];
    /** @var \Illuminate\Support\Collection<int,\App\Models\Training>|array */
    public $trainings = [];

    public function getItemsProperty(): Collection
    {
        // Preferred: if parent passes a trainings collection (already eager-loaded), map it directly.
        if (!empty($this->trainings)) {
            return collect($this->trainings)->map(function ($t) {
                // $t could be array (from ScheduleView mapping) or model
                $sessions = is_array($t) ? ($t['sessions'] ?? []) : ($t->sessions ?? collect());
                $start = is_array($t) ? $t['start_date'] : $t->start_date;
                $end = is_array($t) ? $t['end_date'] : $t->end_date;
                $type = is_array($t) ? ($t['type'] ?? null) : ($t->type ?? null);
                $status = is_array($t) ? ($t['status'] ?? null) : ($t->status ?? null);
                // Aggregate trainer names (unique)
                $trainerNames = collect($sessions)->map(function ($s) {
                    if (is_array($s)) {
                        return $s['trainer']['name'] ?? null;
                    }
                    return $s->trainer->name ?? ($s->trainer->user->name ?? null);
                })->filter()->unique()->values()->toArray();
                return [
                    'id' => is_array($t) ? $t['id'] : $t->id,
                    'name' => is_array($t) ? $t['name'] : $t->name,
                    'type' => $type,
                    'status' => $status,
                    'start_date' => $start,
                    'end_date' => $end,
                    'start_iso' => \Carbon\Carbon::parse($start)->format('Y-m-d'),
                    'day_span' => \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end)) + 1,
                    'sessions' => $sessions,
                    'trainer_names' => $trainerNames,
                ];
            })->sortBy(fn($x) => $x['start_date'])->values();
        }

        // Fallback (backward compatible): derive unique trainings from $days (each training appears only once).
        $raw = collect($this->days)->flatMap(fn($d) => $d['trainings'] ?? [])
            ->groupBy('id')
            ->map(function ($group) {
                $first = $group->first();
                $sessions = collect($first['sessions'] ?? [])->sortBy('day_number')->values();
                $start = $first['start_date'] ?? ($sessions->first()['date'] ?? null);
                $end = $first['end_date'] ?? ($sessions->last()['date'] ?? null);
                $trainerNames = $sessions->map(fn($s) => $s['trainer']['name'] ?? null)->filter()->unique()->values()->toArray();
                return [
                    'id' => $first['id'],
                    'name' => $first['name'],
                    'type' => $first['type'] ?? null,
                    'status' => $first['status'] ?? null,
                    'start_date' => $start,
                    'end_date' => $end,
                    'start_iso' => $start ? \Carbon\Carbon::parse($start)->format('Y-m-d') : null,
                    'day_span' => ($start && $end) ? (\Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end)) + 1) : $sessions->count(),
                    'sessions' => $sessions,
                    'trainer_names' => $trainerNames,
                ];
            })->sortBy(fn($x) => $x['start_date'])->values();
        return $raw;
    }

    public function open(int $id, string $isoStart): void
    {
        // We send the training start date as clickedDate so detail modal can compute initial_day_number.
        $this->dispatch('fullcalendar-open-event', id: $id, clickedDate: $isoStart);
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
        return view('components.training-schedule.agenda-list');
    }
}

