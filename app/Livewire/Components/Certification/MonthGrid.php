<?php

namespace App\Livewire\Components\Certification;

use Livewire\Component;
use App\Models\CertificationSession;
use App\Models\User;
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
        /** @var User|null $user */
        $user = Auth::user();
        $isAdmin = (bool) ($user?->hasRole('admin'));

        // Check if certification is closed
        $isClosed = in_array(strtolower($cert?->status ?? ''), ['closed', 'done', 'completed']);

        // If closed, go directly to detail modal
        if ($isClosed) {
            $this->dispatch('open-detail-certification-modal', $payload);
            return;
        }

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

    public function addForDate(string $isoDate): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $isAdmin = (bool) ($user?->hasRole('admin'));
        if (!$isAdmin) return;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $isoDate)) return;
        $this->dispatch('open-certification-form-date', ['date' => $isoDate]);
    }

    public function render()
    {
        return view('components.certification.month-grid');
    }
}
