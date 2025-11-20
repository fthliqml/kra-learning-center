<?php

namespace App\Livewire\Pages\Certification;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\CertificationSession;
use Livewire\Component;

class CertificationSchedule extends Component
{
    public function groupedSchedules(): Collection
    {
        $sessions = CertificationSession::with(['certification.certificationModule'])
            ->orderBy('date')
            ->get()
            ->map(function ($s) {
                $title = $s->certification?->name
                    ?? $s->certification?->certificationModule?->module_title
                    ?? 'Certification';

                return [
                    'date' => $s->date instanceof Carbon ? $s->date : Carbon::parse($s->date),
                    'title' => $title,
                    'location' => $s->location,
                    'type' => $s->type,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                ];
            });

        return $sessions->groupBy(fn($i) => $i['date']->format('F Y'));
    }

    public function render()
    {
        return view('pages.certification.certification-schedule', [
            'grouped' => $this->groupedSchedules(),
        ]);
    }
}
