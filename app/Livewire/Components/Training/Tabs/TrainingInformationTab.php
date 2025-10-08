<?php

namespace App\Livewire\Components\Training\Tabs;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\Trainer;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;
use Mary\Traits\Toast;

class TrainingInformationTab extends Component
{
    use Toast;

    public int $trainingId;
    public int $dayNumber = 1;

    // Loading state
    public bool $loading = true;

    // Display-only training level fields
    public ?string $name = null;
    public ?string $group_comp = null;
    public ?string $type = null; // reserved
    public ?string $dateRange = null; // "Y-m-d to Y-m-d"

    // Session (current day) display fields
    public ?string $room_name = null;
    public ?string $room_location = null;
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?int $trainer_id = null;
    public ?string $trainer_name = null;

    // Internal caches
    public ?Training $training = null;
    public array $sessions = [];

    protected $listeners = [
        'training-day-changed' => 'onDayChanged',
    ];

    public function mount(int $trainingId, int $dayNumber = 1)
    {
        $this->trainingId = $trainingId;
        $this->dayNumber = max(1, (int) $dayNumber);
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loading = true;
        // Include trainer.user so we can fallback to the user's name if trainer name is null
        $this->training = Training::with(['sessions.trainer.user'])->find($this->trainingId);
        if (!$this->training) {
            $this->error('Training not found');
            $this->loading = false;
            return;
        }

        $this->name = $this->training->name;
        $this->group_comp = $this->training->group_comp;
        $this->type = $this->training->type;

        $start = $this->training->start_date ? Carbon::parse($this->training->start_date)->toDateString() : null;
        $end = $this->training->end_date ? Carbon::parse($this->training->end_date)->toDateString() : null;
        $this->dateRange = ($start ?? '') . ' to ' . ($end ?? '');

        $this->sessions = $this->training->sessions->sortBy('day_number')->values()->toArray();
        $this->hydrateCurrentSessionFields();
        $this->loading = false;
    }

    private function hydrateCurrentSessionFields(): void
    {
        $idx = $this->dayNumber - 1;
        $session = $this->sessions[$idx] ?? null;
        if (!$session)
            return;
        $this->room_name = $session['room_name'] ?? null;
        $this->room_location = $session['room_location'] ?? null;
        $this->start_time = $session['start_time'] ?? null;
        $this->end_time = $session['end_time'] ?? null;
        $this->trainer_id = $session['trainer']['id'] ?? ($session['trainer_id'] ?? null);
        // Determine trainer display name with fallbacks
        $this->trainer_name = $session['trainer']['name'] ?? ($session['trainer']['user']['name'] ?? null);
        if (!$this->trainer_name && $this->trainer_id) {
            // Final fallback: query trainer (with user) once if relation array lacked name
            $trainer = \App\Models\Trainer::with('user')->find($this->trainer_id);
            if ($trainer) {
                $this->trainer_name = $trainer->name ?: ($trainer->user->name ?? null);
            }
        }
        if ($this->start_time)
            $this->start_time = substr($this->start_time, 0, 5);
        if ($this->end_time)
            $this->end_time = substr($this->end_time, 0, 5);
    }

    public function onDayChanged(int $dayNumber): void
    {
        $this->dayNumber = max(1, $dayNumber);
        $this->hydrateCurrentSessionFields();
    }

    private function parseDateRange(): array
    {
        if (!$this->dateRange)
            return [null, null];
        $parts = explode(' to ', $this->dateRange);
        $start = $parts[0] ?? null;
        $end = $parts[1] ?? $parts[0] ?? null;
        return [$start, $end];
    }

    /**
     * Short human readable date range like "6-9 sep" or "28 Sep - 2 Oct".
     * Falls back to raw $dateRange if parsing fails.
     */
    public function getFormattedRangeProperty(): string
    {
        if (!$this->dateRange)
            return '';
        [$start, $end] = $this->parseDateRange();
        if (!$start || !$end)
            return $this->dateRange;
        try {
            $s = Carbon::parse($start);
            $e = Carbon::parse($end);
        } catch (\Throwable $th) {
            return $this->dateRange;
        }
        if ($s->isSameMonth($e) && $s->year === $e->year) {
            return sprintf('%d-%d %s %d', $s->day, $e->day, $e->format('F'), $e->year);
        }
        if ($s->year === $e->year) {
            return sprintf('%d %s - %d %s %d', $s->day, $s->format('F'), $e->day, $e->format('F'), $e->year);
        }
        return sprintf('%d %s %d - %d %s %d', $s->day, $s->format('F'), $s->year, $e->day, $e->format('F'), $e->year);
    }

    public function render()
    {
        return view('components.training.tabs.training-information-tab');
    }
}
