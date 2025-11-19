<?php

namespace App\Livewire\Components\Certification;

use App\Models\CertificationSession;
use Livewire\Component;

class DetailCertificationModal extends Component
{
    public bool $modal = false;
    public ?int $sessionId = null;
    public ?int $selectedSessionId = null;
    public array $selected = [];
    public array $sessionOptions = [];

    protected $listeners = [
        'open-detail-certification-modal' => 'open',
        'close-modal' => 'closeModal',
    ];

    public function open($sessionId)
    {
        $this->modal = true;
        $this->loadSessionData((int) $sessionId);
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
    }

    public function render()
    {
        return view('components.certification.detail-certification-modal', [
            'sessionOptions' => $this->sessionOptions,
        ]);
    }
}
