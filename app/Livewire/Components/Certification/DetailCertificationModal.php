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
    public bool $isClosed = false;

    protected $listeners = [
        'open-detail-certification-modal' => 'open',
        'close-modal' => 'closeModal',
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

    public function triggerSaveDraft(): void
    {
        // Forward action to Close tab component
        $this->dispatch('cert-close-save-draft');
    }

    public function triggerCloseCertification(): void
    {
        // Forward action to Close tab component
        $this->dispatch('cert-close-close');
    }

    private function resetState(): void
    {
        $this->modal = false;
        $this->sessionId = null;
        $this->selectedSessionId = null;
        $this->selected = [];
        $this->sessionOptions = [];
        $this->activeTab = 'information';
        $this->isClosed = false;
    }

    private function loadSessionData(int $id): void
    {
        $this->sessionId = $id;
        $session = CertificationSession::with(['certification.certificationModule', 'certification.sessions'])
            ->find($id);
        if (!$session) return;

        $cert = $session->certification;
        $module = $cert?->certificationModule;

        // Check if certification is closed
        $this->isClosed = in_array(strtolower($cert?->status ?? ''), ['closed', 'done', 'completed']);

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
        if (!in_array($tab, ['information', 'attendance', 'close-certification'])) return;
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('components.certification.detail-certification-modal', [
            'sessionOptions' => $this->sessionOptions,
        ]);
    }
}
