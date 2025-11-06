<?php

namespace App\Livewire\Components\Training;

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
        'confirm-delete-training' => 'onConfirmDelete',
    ];

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
        return collect($period)->map(function ($date, $index) {
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

    public function requestDeleteConfirm(): void
    {
        $id = $this->selectedEvent['id'] ?? null;
        // Dispatch Livewire event to global ConfirmDialog component (positional arguments)
        $this->dispatch('confirm', 'Delete Confirmation', 'Are you sure you want to delete this training along with all sessions and attendance?', 'confirm-delete-training', $id);
    }

    public function closeTraining(): void
    {
        // Ensure a training is selected
        if (!$this->selectedEvent || !isset($this->selectedEvent['id'])) {
            return;
        }

        $trainingId = (int) $this->selectedEvent['id'];

        // Load training first and validate type (only IN/OUT can be closed)
        $training = Training::find($trainingId);
        if (!$training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }
        $typeUpper = strtoupper($training->type ?? '');
        if (!in_array($typeUpper, ['IN', 'OUT'])) {
            // Block closing for K-LEARN or any non IN/OUT types
            $this->error('Close Training is only available for IN/OUT types.', position: 'toast-top toast-center');
            return;
        }

        // Check if there is any pending attendance under any session of this training
        $pendingSession = TrainingSession::where('training_id', $trainingId)
            ->whereHas('attendances', function ($q) {
                $q->where('status', 'pending');
            })
            ->orderBy('day_number')
            ->first();

        if ($pendingSession) {
            // Cancel close, show error toast indicating the specific day number
            $day = $pendingSession->day_number ?? '-';
            $this->error('There are pending attendances on day ' . $day . '.', position: 'toast-top toast-center');
            return;
        }

        // No pending attendance, mark training as done

        try {
            DB::transaction(function () use ($training) {
                $training->status = 'done';
                $training->save();
            });

            // Notify success and close modal
            $this->success('Training has been closed.', position: 'toast-top toast-center');
            $this->dispatch('training-closed', ['id' => $training->id]);
            $this->modal = false;
        } catch (\Throwable $e) {
            $this->error('Failed to close training.', position: 'toast-top toast-center');
        }
    }

    public function onConfirmDelete($id = null): void
    {
        // Ensure the confirmation corresponds to the currently opened training (if id is passed)
        if ($id && isset($this->selectedEvent['id']) && (int) $id !== (int) $this->selectedEvent['id']) {
            return;
        }
        $this->deleteTraining();
    }

    public function deleteTraining()
    {
        if (!$this->selectedEvent || !isset($this->selectedEvent['id'])) {
            return;
        }
        $id = $this->selectedEvent['id'];
        try {
            DB::transaction(function () use ($id) {
                // Load training with sessions to collect session IDs
                $training = Training::with('sessions')->find($id);
                if (!$training)
                    return;

                $sessionIds = $training->sessions->pluck('id')->all();
                if (!empty($sessionIds)) {
                    // Delete attendances under sessions
                    TrainingAttendance::whereIn('session_id', $sessionIds)->delete();
                }
                // Delete sessions
                \App\Models\TrainingSession::where('training_id', $id)->delete();
                // Delete assessments if relationship exists on table (best effort)
                if (method_exists($training, 'assessments')) {
                    $training->assessments()->delete();
                }
                // Finally delete training
                $training->delete();
            });

            // Notify parent and close
            $this->dispatch('training-deleted', ['id' => $id]);
            $this->success('Training deleted.', position: 'toast-top toast-center');
            $this->modal = false;
            // Close confirm dialog and stop spinner
            $this->dispatch('confirm-done');
        } catch (\Throwable $e) {
            $this->error('Failed to delete training.');
            $this->dispatch('confirm-done');
        }
    }

    public function render()
    {
        return view('components.training.detail-training-modal', [
            'trainingDates' => $this->trainingDates(),
        ]);
    }
}

