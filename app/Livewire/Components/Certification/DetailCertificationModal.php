<?php

namespace App\Livewire\Components\Certification;

use App\Models\CertificationSession;
use App\Models\Certification;
use App\Models\CertificationAttendance;
use App\Models\CertificationScore;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailCertificationModal extends Component
{
    public bool $modal = false;
    public ?int $sessionId = null;
    public ?int $selectedSessionId = null;
    public array $selected = [];
    public array $sessionOptions = [];
    public string $activeTab = 'information';

    protected $listeners = [
        'open-detail-certification-modal' => 'open',
        'close-modal' => 'closeModal',
        'confirm-delete-certification' => 'onConfirmDelete',
    ];

    public function open($payload)
    {
        $sessionId = null;
        if (is_array($payload)) {
            $sessionId = $payload['session_id'] ?? $payload['id'] ?? null;
        } else {
            $sessionId = (int) $payload;
        }
        if (!$sessionId) return;
        $this->modal = true;
        $this->loadSessionData((int) $sessionId);
        $this->activeTab = 'information';
    }

    public function selectSession(?int $id = null): void
    {
        $id = $id ?? ($this->selectedSessionId ? (int) $this->selectedSessionId : null);
        if (!$id) return;
        $this->loadSessionData($id);
    }

    public function updatedSelectedSessionId($value): void
    {
        if ($value) {
            $this->loadSessionData((int) $value);
        }
    }

    public function updated($name, $value)
    {
        if ($name === 'selectedSessionId' && $value) {
            $this->loadSessionData((int) $value);
        }
    }

    public function closeModal(): void
    {
        $this->modal = false;
    }

    private function resetState(): void
    {
        $this->modal = false;
        $this->sessionId = null;
        $this->selectedSessionId = null;
        $this->selected = [];
        $this->sessionOptions = [];
        $this->activeTab = 'information';
    }

    private function loadSessionData(int $id): void
    {
        $this->sessionId = $id;
        $session = CertificationSession::with(['certification.certificationModule', 'certification.sessions'])
            ->find($id);
        if (!$session) return;

        $cert = $session->certification;
        $module = $cert?->certificationModule;

        $this->selected = [
            'session_id' => $session->id,
            'certification_id' => $cert?->id,
            'title' => $cert?->name ?? ($module?->module_title ?? 'Certification'),
            'type' => strtoupper($session->type ?? ''),
            'date' => $session->date,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'location' => $session->location,
            'module' => [
                'module_title' => $module?->module_title,
                'level' => $module?->level,
                'group_certification' => $module?->group_certification,
            ],
        ];

        // Build session selector options for this certification
        $this->sessionOptions = $cert?->sessions?->sortBy('date')->values()->map(function ($s) {
            $label = (\Carbon\Carbon::parse($s->date)->format('d M Y')) . ' • ' . strtoupper($s->type ?? '');
            return ['id' => $s->id, 'name' => $label];
        })->toArray() ?? [];

        $this->selectedSessionId = $session->id;
        // Ensure re-render even with custom select components
        $this->dispatch('$refresh');
        // Notify nested tabs (e.g., attendance) to reload for this session
        $this->dispatch('certification-session-changed', $session->id);
    }

    public function setActiveTab(string $tab): void
    {
        if (!in_array($tab, ['information', 'attendance'])) return;
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('components.certification.detail-certification-modal', [
            'sessionOptions' => $this->sessionOptions,
        ]);
    }

    public function requestDeleteConfirm(): void
    {
        $certId = $this->selected['certification_id'] ?? null;
        if (!$certId) return;
        $this->dispatch('confirm', 'Delete Confirmation', 'Are you sure you want to delete this certification schedule? All theory & practical sessions plus attendance and scores will be removed.', 'confirm-delete-certification', $certId);
    }

    public function onConfirmDelete($id = null): void
    {
        $certId = $this->selected['certification_id'] ?? null;
        if ($id && $certId && (int)$id !== (int)$certId) return; // mismatch
        $this->deleteCertification();
    }

    public function deleteCertification(): void
    {
        $certId = $this->selected['certification_id'] ?? null;
        if (!$certId) return;
        try {
            DB::transaction(function () use ($certId) {
                $cert = Certification::with(['sessions.attendances', 'sessions.scores', 'participants.attendances', 'participants.scores'])->find($certId);
                if (!$cert) return;
                // Delete session-linked records first
                foreach ($cert->sessions as $session) {
                    $session->attendances()->delete();
                    $session->scores()->delete();
                }
                // Delete sessions
                CertificationSession::where('certification_id', $certId)->delete();
                // Optionally remove participant-related scores/attendances already handled via sessions; remove participants
                $cert->participants()->delete();
                // Finally delete certification
                $cert->delete();
            });
            $this->dispatch('certification-deleted', ['id' => $certId]);
            $this->dispatch('confirm-done');
            $this->dispatch('notify', type: 'success', message: 'Certification schedule deleted.');
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to delete certification.');
        }
    }
}
