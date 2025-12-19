<?php

namespace App\Livewire\Components\Certification\Tabs;

use App\Models\CertificationSession;
use App\Models\CertificationAttendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;
use Carbon\Carbon;

class CertificationAttendanceTab extends Component
{
    use Toast;

    public int $sessionId;
    public bool $loading = true;
    public bool $readOnly = false;
    public array $attendance = [];
    public array $participants = [];

    protected $listeners = [
        'certification-session-changed' => 'onSessionChanged',
    ];

    public function mount(int $sessionId)
    {
        $this->sessionId = $sessionId;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loading = true;
        $session = CertificationSession::with(['certification.participants.employee', 'attendances.participant'])
            ->find($this->sessionId);
        if (!$session) {
            $this->error('Session not found');
            $this->loading = false;
            return;
        }
        $cert = $session->certification;
        $this->readOnly = in_array(strtolower($cert->status ?? ''), ['closed', 'done', 'completed']);
        // Leader role can only view (readonly)
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        // Check if user is in leadership position
        if ($user && method_exists($user, 'hasAnyPosition') && $user->hasAnyPosition(['section_head', 'department_head', 'division_head', 'director'])) {
            $this->readOnly = true;
        }

        // Build participant list
        $participantMap = [];
        foreach ($cert->participants as $p) {
            $participantMap[$p->id] = [
                'id' => $p->id,
                'employee_id' => $p->employee_id,
                'name' => $p->employee->name ?? null,
                'nrp' => $p->employee->nrp ?? ($p->employee->NRP ?? null),
                'section' => $p->employee->section ?? null,
            ];
        }
        $this->participants = array_values($participantMap);

        // Hydrate attendance for this session
        $records = CertificationAttendance::where('session_id', $session->id)->get();
        $map = [];
        foreach ($records as $r) {
            $map[$r->participant_id] = [
                'status' => $r->status,
                'remark' => $r->absence_notes,
            ];
        }
        $this->attendance = $map;
        $this->loading = false;
    }

    public function onSessionChanged(int $sessionId): void
    {
        $this->sessionId = $sessionId;
        $this->loadData();
    }

    public function save(): void
    {
        if ($this->readOnly) {
            $this->error("You can't modify attendance for a closed certification.", position: 'toast-top toast-center');
            return;
        }
        foreach ($this->attendance as $pid => $row) {
            if (!$pid) continue;
            $status = $row['status'] ?? null;
            if (!in_array($status, ['present', 'absent'], true)) {
                continue;
            }
            CertificationAttendance::updateOrCreate([
                'session_id' => $this->sessionId,
                'participant_id' => $pid,
            ], [
                'status' => $status,
                'absence_notes' => $row['remark'] ?? null,
                'recorded_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        $this->success('Attendance saved', position: 'toast-top toast-center');
        $this->dispatch('certification-attendance-updated', id: $this->sessionId);
    }

    public function placeholder()
    {
        return view('components.skeletons.training-attendance-tab');
    }

    public function render()
    {
        return view('components.certification.tabs.certification-attendance-tab');
    }
}
