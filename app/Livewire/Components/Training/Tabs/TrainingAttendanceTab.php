<?php

namespace App\Livewire\Components\Training\Tabs;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\TrainingAttendance;
use Livewire\Component;
use Mary\Traits\Toast;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TrainingAttendanceTab extends Component
{
    use Toast;

    public int $trainingId;
    public int $dayNumber = 1;

    public bool $loading = true;

    // attendance[employee_id] = ['status'=>..., 'remark'=>...]
    public array $attendance = [];
    public array $employees = [];

    public ?Training $training = null;
    public array $sessions = [];
    public bool $readOnly = false; // instructors cannot edit when training is done

    protected $listeners = [
        'training-day-changed' => 'onDayChanged',
        'training-info-updated' => 'reloadTrainingMeta',
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
        $this->training = Training::with(['sessions.attendances.employee'])->find($this->trainingId);
        if (!$this->training) {
            $this->error('Training not found');
            $this->loading = false;
            return;
        }
        // Determine read-only mode: training is closed (done)
        // Everyone (including admin) cannot edit attendance for closed training
        $this->readOnly = strtolower($this->training->status ?? '') === 'done';
        $this->sessions = $this->training->sessions->sortBy('day_number')->values()->toArray();

        // derive employees from all attendance records across sessions
        $employeeMap = [];
        foreach ($this->training->attendances()->with('employee')->get() as $att) {
            if ($att->employee) {
                $employeeMap[$att->employee->id] = [
                    'id' => $att->employee->id,
                    'name' => $att->employee->name,
                    'NRP' => $att->employee->NRP ?? null,
                    'section' => $att->employee->section ?? null,
                ];
            }
        }
        $this->employees = array_values($employeeMap);

        $this->hydrateDayAttendance();
        $this->loading = false;
    }

    private function currentSessionId(): ?int
    {
        $idx = $this->dayNumber - 1;
        return $this->sessions[$idx]['id'] ?? null;
    }

    private function hydrateDayAttendance(): void
    {
        $sessionId = $this->currentSessionId();
        if (!$sessionId)
            return;
        $records = TrainingAttendance::where('session_id', $sessionId)->get();
        $map = [];
        foreach ($records as $r) {
            $map[$r->employee_id] = [
                'status' => $r->status,
                'remark' => $r->notes,
            ];
        }
        $this->attendance = $map;
    }

    public function onDayChanged(int $dayNumber): void
    {
        $this->dayNumber = max(1, $dayNumber);
        $this->hydrateDayAttendance();
    }

    public function reloadTrainingMeta(): void
    {
        // After info updated we may have more sessions (extended date range)
        $this->loadData();
    }

    public function save(): void
    {
        if ($this->readOnly) {
            $this->error("You can't modify attendance for a closed training.", position: 'toast-top toast-center');
            return;
        }
        $sessionId = $this->currentSessionId();
        if (!$sessionId)
            return;
        foreach ($this->attendance as $employeeId => $row) {
            if (!$employeeId)
                continue;
            TrainingAttendance::updateOrCreate([
                'session_id' => $sessionId,
                'employee_id' => $employeeId,
            ], [
                'status' => $row['status'] ?? null,
                'notes' => $row['remark'] ?? null,
                'recorded_at' => Carbon::now(),
            ]);
        }
        $this->success('Attendance saved', position: 'toast-top toast-center');
        $this->dispatch('training-attendance-updated', id: $this->trainingId, day: $this->dayNumber);
    }

    public function placeholder()
    {
        return view('components.skeletons.training-attendance-tab');
    }

    public function render()
    {
        return view('components.training.tabs.training-attendance-tab');
    }
}
