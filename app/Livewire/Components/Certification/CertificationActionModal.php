<?php

namespace App\Livewire\Components\Certification;

use App\Models\CertificationSession;
use App\Models\Certification;
use Livewire\Component;

class CertificationActionModal extends Component
{
    public bool $show = false;
    public ?int $certificationId = null;
    public ?int $sessionId = null; // session used to open
    public string $title = '';

    protected $listeners = [
        'open-certification-action' => 'open',
    ];

    public function open(int $sessionId): void
    {
        $session = CertificationSession::with('certification.certificationModule', 'certification.sessions')->find($sessionId);
        if (!$session) return;
        $cert = $session->certification;
        if (!$cert) return;
        $this->sessionId = $session->id;
        $this->certificationId = $cert->id;
        $this->title = $cert->name ?: ($cert->certificationModule?->module_title ?? 'Certification');
        $this->show = true;
    }

    public function viewDetail(): void
    {
        if (!$this->certificationId) return;
        $cert = Certification::with('sessions')->find($this->certificationId);
        $firstSessionId = $cert?->sessions?->sortBy('date')->first()?->id;
        if ($firstSessionId) {
            $this->dispatch('open-detail-certification-modal', $firstSessionId);
        }
        $this->close();
    }

    public function editCertification(): void
    {
        if (!$this->certificationId) return;
        $this->dispatch('open-certification-form-edit', $this->certificationId);
        $this->close();
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('components.certification.certification-action-modal');
    }
}
