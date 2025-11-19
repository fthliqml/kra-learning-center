<?php

namespace App\Livewire\Components\Certification;

use App\Models\CertificationSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class ScheduleView extends Component
{
    public string $activeView = 'month';
    public int $currentMonth;
    public int $currentYear;
    public int $calendarVersion = 0;
    public array $days = [];
    /** Counts of sessions per month for the current year (1..12) */
    public array $monthlySessionCounts = [];
    protected $listeners = ['certification-deleted' => 'refresh', 'certification-created' => 'refresh'];

    public function mount(): void
    {
        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
        $this->refresh();
    }

    public function getMonthNameProperty(): string
    {
        return Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    public function setView(string $view): void
    {
        if (!in_array($view, ['month', 'agenda'])) return;
        if ($this->activeView === $view) return;
        $this->activeView = $view;
        $this->refresh();
    }

    public function previousMonth(): void
    {
        if ($this->currentMonth === 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
        $this->refresh();
    }

    public function nextMonth(): void
    {
        if ($this->currentMonth === 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
        $this->refresh();
    }

    public function setMonth(int $month): void
    {
        if ($month < 1 || $month > 12) return;
        $this->currentMonth = $month;
        $this->refresh();
    }

    private function calendarRange(): array
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        return [
            $startOfMonth->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
        ];
    }

    private function strictMonthRange(): array
    {
        $start = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        return [$start->toDateString(), $end->toDateString()];
    }

    private function fetchSessions(string $start, string $end)
    {
        return CertificationSession::with(['certification.certificationModule'])
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();
    }

    private function recomputeDays(): void
    {
        [$s, $e] = $this->activeView === 'agenda' ? $this->strictMonthRange() : $this->calendarRange();
        $start = Carbon::parse($s);
        $end = Carbon::parse($e);
        $sessions = $this->fetchSessions($s, $e);
        $byDate = $sessions->groupBy(fn($s) => Carbon::parse($s->date)->format('Y-m-d'));

        $days = [];
        $cur = $start->copy();
        while ($cur <= $end) {
            $iso = $cur->format('Y-m-d');
            $items = ($byDate[$iso] ?? collect())->map(function ($s) use ($iso) {
                $title = $s->certification?->name
                    ?? $s->certification?->certificationModule?->module_title
                    ?? 'Certification';
                return [
                    'id' => $s->id,
                    'title' => $title,
                    'type' => strtoupper($s->type ?? ''),
                    'location' => $s->location,
                    'time' => [
                        'start' => $s->start_time,
                        'end' => $s->end_time,
                    ],
                    'iso_date' => $iso,
                ];
            })->values();

            $days[] = [
                'date' => $cur->copy(),
                'isCurrentMonth' => $cur->month === $this->currentMonth,
                'isToday' => $cur->isToday(),
                'sessions' => $items,
            ];
            $cur->addDay();
        }
        $this->days = $days;
    }

    public function refresh(): void
    {
        $this->recomputeDays();
        $this->monthlySessionCounts = $this->buildYearCounts($this->currentYear);
        $this->calendarVersion++;
    }

    /**
     * Count certification sessions per month for a given year.
     */
    private function buildYearCounts(int $year): array
    {
        $counts = array_fill(1, 12, 0);
        $yearStart = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::createFromDate($year, 12, 31)->endOfDay();

        $sessions = CertificationSession::select('date')
            ->whereDate('date', '>=', $yearStart)
            ->whereDate('date', '<=', $yearEnd)
            ->get();

        foreach ($sessions as $s) {
            $m = Carbon::parse($s->date)->month;
            $counts[$m] = ($counts[$m] ?? 0) + 1;
        }
        return $counts;
    }

    public function render()
    {
        return view('components.certification.schedule-view');
    }
}
