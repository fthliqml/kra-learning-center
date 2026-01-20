<?php

namespace App\Livewire\Components\TrainingSchedule;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\TrainingAttendance; // only for delete cleanup
use Carbon\CarbonPeriod;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

class DetailTrainingModal extends Component
{
    use Toast;

    public $modal = false;
    public $selectedEvent = null; // associative array (minimal training info)
    public $dayNumber = 1;
    public $activeTab = 'information';

    // Legacy edit modes removed; edits handled in child components

    protected $listeners = [
        'open-detail-training-modal' => 'open',
        'training-closed' => 'onTrainingClosed',
        'close-modal' => 'closeModal',
    ];

    public function triggerSaveDraft(): void
    {
        // Forward action to Close tab component
        $this->dispatch('training-close-save-draft');
    }

    public function triggerCloseTraining(): void
    {
        // Forward action to Close tab component
        $this->dispatch('training-close-close');
    }

    // Session helper methods removed; handled inside child tabs

    public function open($payload)
    {
        $this->resetModalState();
        if (!is_array($payload) || !isset($payload['id']))
            return;

        $this->selectedEvent = [
            'id' => $payload['id'],
            'name' => $payload['name'] ?? null,
            'group_comp' => $payload['group_comp'] ?? null,
            'type' => $payload['type'] ?? ($payload['training_type'] ?? null),
            'status' => $payload['status'] ?? null,
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
        ];

        // If calendar provided a specific day to open, set it now (validate range later)
        if (isset($payload['initial_day_number']) && is_numeric($payload['initial_day_number'])) {
            $this->dayNumber = max(1, (int) $payload['initial_day_number']);
        }

        $this->modal = true;

        // Notify front-end that detail is ready (browser event). Fallback approach without dispatchBrowserEvent helper.
        if (method_exists($this, 'dispatchBrowserEvent')) {
            // Livewire v2 style
            $this->dispatchBrowserEvent('training-detail-ready');
        } else {
            // Livewire v3: emit to JS via dispatch + window listener (listen on 'training-detail-ready')
            $this->dispatch('training-detail-ready');
        }
    }

    public function resetModalState()
    {
        $this->modal = false;
        $this->selectedEvent = null;
        $this->dayNumber = 1;
        $this->activeTab = 'information';
    }

    // Legacy update & date parsing logic removed; handled inside child tabs

    public function updatedDayNumber()
    {
        $this->dayNumber = (int) $this->dayNumber;
        $this->dispatch('training-day-changed', $this->dayNumber);
    }

    public function trainingDates()
    {
        if (!$this->selectedEvent)
            return collect();
        $period = CarbonPeriod::create($this->selectedEvent['start_date'], $this->selectedEvent['end_date']);
        return collect($period)->map(function (\Carbon\Carbon $date, $index) {
            return [
                'id' => $index + 1,
                'name' => $date->format('d M Y'),
            ];
        })->values();
    }

    public function closeModal()
    {
        $this->modal = false;
    }

    public function onTrainingClosed($payload = null)
    {
        // Refresh the selected event if needed
        if ($payload && isset($payload['id']) && $this->selectedEvent && $this->selectedEvent['id'] == $payload['id']) {
            $this->selectedEvent['status'] = 'done';
        }
        // Close the modal
        $this->modal = false;
    }

    public function render()
    {
        return view('components.training-schedule.detail-training-modal', [
            'trainingDates' => $this->trainingDates(),
        ]);
    }
}
