<?php

namespace App\Livewire\Pages\Training;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Schedule extends Component
{

    public function mount(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            return;
        }

        $participantsParam = (string) request()->query('participants', '');
        if (trim($participantsParam) === '') {
            return;
        }

        $participants = collect(explode(',', $participantsParam))
            ->map(fn($v) => (int) trim((string) $v))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->toArray();

        if (empty($participants)) {
            return;
        }

        $prefillModuleId = (int) request()->query('prefill_module_id', 0);
        $prefillCompetencyId = (int) request()->query('prefill_competency_id', 0);

        $payload = ['participants' => $participants];
        if ($prefillModuleId > 0) {
            $payload['prefill_module_id'] = $prefillModuleId;
        }
        if ($prefillCompetencyId > 0) {
            $payload['prefill_competency_id'] = $prefillCompetencyId;
        }

        $this->dispatch('open-add-training-modal', $payload);
    }

    public function render()
    {
        return view('pages.training.training-schedule');
    }
}
