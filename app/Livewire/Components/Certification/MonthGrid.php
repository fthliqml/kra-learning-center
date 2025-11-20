<?php

namespace App\Livewire\Components\Certification;

use Livewire\Component;
use App\Models\CertificationSession;
use Illuminate\Support\Facades\Auth;

class MonthGrid extends Component
{
    /** @var array<int,array{date:mixed,isCurrentMonth:bool,isToday:bool,sessions:array}> */
    public array $days = [];
    public string $monthName = '';

    public function open(int $sessionId): void
    {
        $session = CertificationSession::with('certification')->find($sessionId);
        if (!$session) return;
        $cert = $session->certification;
        $payload = [
            'session_id' => $session->id,
            'certification_id' => $cert?->id,
        ];
        $user = Auth::user();
        $isAdmin = $user && strtolower($user->role ?? '') === 'admin';
        if ($isAdmin) {
            $this->dispatch('open-action-choice', [
                'title' => 'Certification Action',
                'message' => 'What would you like to do with this certification?',
                'payload' => $payload,
                'actions' => [
                    [
                        'label' => 'View Detail',
                        'event' => 'open-detail-certification-modal',
                        'variant' => 'outline',
                    ],
                    [
                        'label' => 'Edit Certification',
                        'event' => 'open-certification-form-edit',
                        'variant' => 'primary',
                    ],
                ],
            ]);
        } else {
            // Non-admins go straight to details
            $this->dispatch('open-detail-certification-modal', $payload);
        }
    }

    public function placeholder()
    {
        return view('components.skeletons.full-calendar');
    }

    public function render()
    {
        return view('components.certification.month-grid');
    }
}
