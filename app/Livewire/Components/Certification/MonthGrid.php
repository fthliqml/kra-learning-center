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
        $actions = [
            [
                'label' => 'View Detail',
                'event' => 'open-detail-certification-modal',
                'variant' => 'outline',
            ],
        ];
        $user = Auth::user();
        if ($user && strtolower($user->role ?? '') === 'admin') {
            $actions[] = [
                'label' => 'Edit Certification',
                'event' => 'open-certification-form-edit',
                'variant' => 'primary',
            ];
        }
        $this->dispatch('open-action-choice', [
            'title' => 'Certification Action',
            'message' => 'What would you like to do with this certification?',
            'payload' => $payload,
            'actions' => $actions,
        ]);
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
