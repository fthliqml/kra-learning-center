<?php

namespace App\Livewire\Components\Training;

use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

class DetailTrainingModal extends Component
{
    use Toast;

    public $modal = false;
    public $selectedEvent = null; // associative array of training
    public $dayNumber = 1;
    public $trainingDateRange = null;
    public $employees = [];// will hold collection of employee models
    public $attendances = [];
    public $sessions = [];
    public $trainer = [];
    public $trainers = [];

    public $editModes = [
        'training_name' => false,
        'date' => false,
        'location' => false,
        'trainer' => false,
        'time' => false,
        'group_comp' => false,
    ];

    protected $listeners = [
        'open-detail-training-modal' => 'open',
        'confirm-delete-training' => 'onConfirmDelete',
    ];

    public function getCurrentSessionProperty()
    {
        return $this->sessions[(int) $this->dayNumber - 1] ?? null;
    }

    private function sessionIndex(): int
    {
        return max(0, (int) $this->dayNumber - 1);
    }

    private function sessionExists(): bool
    {
        return isset($this->sessions[$this->sessionIndex()]);
    }

    public function open($payload)
    {
        $this->resetModalState();
        if (!is_array($payload) || !isset($payload['id']))
            return;

        // Load trainer options once (fallback to user name)
        $this->trainers = Trainer::with('user')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name ?: ($t->user->name ?? null),
            ])->toArray();

        $this->selectedEvent = [
            'id' => $payload['id'],
            'name' => $payload['name'] ?? null,
            'group_comp' => $payload['group_comp'] ?? null,
            'type' => $payload['type'] ?? ($payload['training_type'] ?? null),
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
        ];

        $this->sessions = $payload['sessions'] ?? [];

        // If calendar provided a specific day to open, set it now (validate range later)
        if (isset($payload['initial_day_number']) && is_numeric($payload['initial_day_number'])) {
            $this->dayNumber = max(1, (int) $payload['initial_day_number']);
        }

        // Normalize employees to objects with expected properties (id, NRP, name, section)
        $this->employees = collect($payload['employees'] ?? [])
            ->filter(fn($e) => isset($e['id']))
            ->map(function ($e) {
                return (object) [
                    'id' => $e['id'],
                    'NRP' => $e['NRP'] ?? null,
                    'name' => $e['name'] ?? null,
                    'section' => $e['section'] ?? null,
                ];
            })
            ->values();

        foreach ($this->sessions as $session) {
            if (!isset($session['attendances']) || !is_array($session['attendances']))
                continue;
            foreach ($session['attendances'] as $attendance) {
                if (!isset($attendance['employee_id']))
                    continue;
                $this->attendances[$session['day_number']][$attendance['employee_id']] = [
                    'status' => $attendance['status'] ?? null,
                    'remark' => $attendance['remark'] ?? null,
                ];
            }
        }

        // Set initial trainer based on chosen dayNumber (fallback day 1)
        $initialIndex = max(0, $this->dayNumber - 1);
        $initialTrainer = $this->sessions[$initialIndex]['trainer'] ?? ($this->sessions[0]['trainer'] ?? null);
        $this->trainer = $initialTrainer ? [
            'id' => $initialTrainer['id'],
            'name' => $initialTrainer['name']
        ] : ['id' => null, 'name' => null];

        $this->modal = true;

        // Ensure attendance for selected day is hydrated (mirrors updatedDayNumber logic without duplicate code)
        $this->updatedDayNumber();

        // Prefill date range input for edit mode convenience (flatpickr range expects 'Y-m-d to Y-m-d')
        $this->trainingDateRange = $this->selectedEvent['start_date'] . ' to ' . $this->selectedEvent['end_date'];

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
        $this->trainingDateRange = null;
        $this->employees = [];
        $this->attendances = [];
        $this->sessions = [];
        $this->trainer = [];
        $this->trainers = [];
        $this->editModes = array_map(fn() => false, $this->editModes);
    }

    public function updatedDayNumber()
    {
        $this->dayNumber = (int) $this->dayNumber;
        if (!$this->selectedEvent || !$this->sessionExists())
            return;

        $session = $this->sessions[$this->sessionIndex()];
        $sessionTrainer = $session['trainer'] ?? null;
        $this->trainer = $sessionTrainer ? [
            'id' => $sessionTrainer['id'] ?? null,
            'name' => $sessionTrainer['name'] ?? null,
        ] : ['id' => null, 'name' => null];

        if (!isset($this->attendances[$this->dayNumber]) && !empty($session['attendances'])) {
            foreach ($session['attendances'] as $attendance) {
                $this->attendances[$this->dayNumber][$attendance['employee_id']] = [
                    'status' => $attendance['status'],
                    'remark' => $attendance['remark'],
                ];
            }
        }
    }

    // When trainer.id changes (from select), sync the readable name so that when edit mode closes UI shows updated name
    public function updatedTrainerId($value)
    {
        if (!$value) {
            $this->trainer = ['id' => null, 'name' => null];
            return;
        }
        $found = collect($this->trainers)->firstWhere('id', (int) $value);
        if ($found) {
            $this->trainer['name'] = $found['name'];
        }
    }

    private function parseDateRange($dateRange): array
    {
        $dates = explode(' to ', $dateRange);
        $start = $dates[0] ?? null;
        $end = $dates[1] ?? $dates[0] ?? null;
        return [
            'start' => $start ? Carbon::parse($start)->format('Y-m-d') : null,
            'end' => $end ? Carbon::parse($end)->format('Y-m-d') : null,
        ];
    }

    // When user edits date range live (if not deferred) keep selectedEvent shadow in sync for display after toggle off
    public function updatedTrainingDateRange($value)
    {
        if (!$value)
            return;
        $parsed = $this->parseDateRange($value);
        if ($parsed['start'])
            $this->selectedEvent['start_date'] = $parsed['start'];
        if ($parsed['end'])
            $this->selectedEvent['end_date'] = $parsed['end'];
    }

    public function update()
    {
        if (!$this->selectedEvent)
            return;

        $dates = $this->parseDateRange($this->trainingDateRange);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        $updateData = [
            'name' => $this->selectedEvent['name'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'group_comp' => $this->selectedEvent['group_comp'] ?? null,
        ];
        $updateData = array_filter($updateData, fn($v) => !is_null($v));

        Training::where('id', $this->selectedEvent['id'])->update($updateData);

        $updatedSessionPayload = null;
        if ($this->currentSession) {
            $newSessionData = [
                'room_name' => $this->currentSession['room_name'] ?? null,
                'room_location' => $this->currentSession['room_location'] ?? null,
                'trainer_id' => $this->trainer['id'] ?? null,
                'start_time' => $this->currentSession['start_time'] ?? null,
                'end_time' => $this->currentSession['end_time'] ?? null,
            ];
            TrainingSession::where('id', $this->currentSession['id'])->update($newSessionData);

            // Patch local sessions array to keep UI in sync
            $idx = $this->sessionIndex();
            $this->sessions[$idx]['room_name'] = $newSessionData['room_name'];
            $this->sessions[$idx]['room_location'] = $newSessionData['room_location'];
            $this->sessions[$idx]['start_time'] = $newSessionData['start_time'];
            $this->sessions[$idx]['end_time'] = $newSessionData['end_time'];
            $this->sessions[$idx]['trainer'] = $this->trainer['id'] ? [
                'id' => $this->trainer['id'],
                'name' => $this->trainer['name']
            ] : null;

            $updatedSessionPayload = [
                'id' => $this->sessions[$idx]['id'],
                'day_number' => $this->sessions[$idx]['day_number'],
                'room_name' => $this->sessions[$idx]['room_name'],
                'room_location' => $this->sessions[$idx]['room_location'],
                'start_time' => $this->sessions[$idx]['start_time'] ?? null,
                'end_time' => $this->sessions[$idx]['end_time'] ?? null,
                'trainer' => $this->sessions[$idx]['trainer'],
            ];
        }

        // If date range increased, create missing sessions by cloning last session's room/location/trainer
        // Calculate desired number of days based on new range
        $newDays = null;
        if ($startDate && $endDate) {
            try {
                $newDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            } catch (\Throwable $e) {
                $newDays = null;
            }
        }
        $existingCount = count($this->sessions);
        if ($newDays && $newDays > $existingCount) {
            $last = $existingCount > 0 ? $this->sessions[$existingCount - 1] : null;
            $seedRoom = $last['room_name'] ?? null;
            $seedLocation = $last['room_location'] ?? null;
            $seedTrainerId = $last['trainer']['id'] ?? null;
            $seedTrainerName = $last['trainer']['name'] ?? null;
            $seedStartTime = $last['start_time'] ?? null;
            $seedEndTime = $last['end_time'] ?? null;
            $base = Carbon::parse($startDate);
            for ($i = $existingCount + 1; $i <= $newDays; $i++) {
                $sessionDate = $base->copy()->addDays($i - 1)->format('Y-m-d');
                $created = TrainingSession::create([
                    'training_id' => $this->selectedEvent['id'],
                    'day_number' => $i,
                    'date' => $sessionDate,
                    'start_time' => $seedStartTime,
                    'end_time' => $seedEndTime,
                    'room_name' => $seedRoom,
                    'room_location' => $seedLocation,
                    'trainer_id' => $seedTrainerId,
                ]);
                $this->sessions[] = [
                    'id' => $created->id,
                    'day_number' => $i,
                    'date' => $sessionDate,
                    'start_time' => $seedStartTime,
                    'end_time' => $seedEndTime,
                    'room_name' => $seedRoom,
                    'room_location' => $seedLocation,
                    'trainer' => $seedTrainerId ? [
                        'id' => $seedTrainerId,
                        'name' => $seedTrainerName,
                    ] : null,
                    'attendances' => [],
                ];
            }
        }

        $currentSessionId = $this->sessions[$this->dayNumber - 1]['id'] ?? null;
        if ($currentSessionId) {
            foreach ($this->employees as $employee) {
                if (!is_object($employee) || !isset($employee->id))
                    continue;
                $data = $this->attendances[$this->dayNumber][$employee->id] ?? null;
                if ($data) {
                    TrainingAttendance::updateOrCreate(
                        [
                            'session_id' => $currentSessionId,
                            'employee_id' => $employee->id,
                        ],
                        [
                            'status' => $data['status'],
                            'notes' => $data['remark'],
                        ]
                    );
                }
            }
        }

        // Emit minimal update payload so calendar can update locally; parent may trigger full refresh on date change
        $this->dispatch('training-updated', [
            'id' => $this->selectedEvent['id'],
            'name' => $this->selectedEvent['name'],
            'start_date' => $updateData['start_date'] ?? $this->selectedEvent['start_date'],
            'end_date' => $updateData['end_date'] ?? $this->selectedEvent['end_date'],
            'group_comp' => $updateData['group_comp'] ?? $this->selectedEvent['group_comp'] ?? null,
            'session' => $updatedSessionPayload,
        ]);
        $this->success('Successfully updated data!', position: 'toast-top toast-center');
        $this->modal = false;
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

    public function render()
    {
        return view('components.training.detail-training-modal', [
            'trainingDates' => $this->trainingDates(),
        ]);
    }

    public function requestDeleteConfirm(): void
    {
        $id = $this->selectedEvent['id'] ?? null;
        // Dispatch Livewire event to global ConfirmDialog component (positional arguments)
        $this->dispatch('confirm', 'Delete Confirmation', 'Are you sure you want to delete this training along with all sessions and attendance?', 'confirm-delete-training', $id);
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
        } catch (\Throwable $e) {
            $this->error('Failed to delete training.');
        }
    }
}
