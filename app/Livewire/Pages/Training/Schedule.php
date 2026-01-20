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

        if (!empty($participants)) {
            $this->dispatch('open-add-training-modal', ['participants' => $participants]);
        }
    }

    public function render()
    {
        return view('pages.training.training-schedule');
    }
}
