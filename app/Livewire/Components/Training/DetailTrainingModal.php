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
    ];

    protected $listeners = [
        'open-detail-training-modal' => 'open',
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
            'name' => $payload['name'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
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
            foreach ($session['attendances'] as $attendance) {
                $this->attendances[$session['day_number']][$attendance['employee_id']] = [
                    'status' => $attendance['status'],
                    'remark' => $attendance['remark'],
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
        ];
        $updateData = array_filter($updateData, fn($v) => !is_null($v));

        Training::where('id', $this->selectedEvent['id'])->update($updateData);

        $updatedSessionPayload = null;
        if ($this->currentSession) {
            $newSessionData = [
                'room_name' => $this->currentSession['room_name'] ?? null,
                'room_location' => $this->currentSession['room_location'] ?? null,
                'trainer_id' => $this->trainer['id'] ?? null,
            ];
            TrainingSession::where('id', $this->currentSession['id'])->update($newSessionData);

            // Patch local sessions array to keep UI in sync
            $idx = $this->sessionIndex();
            $this->sessions[$idx]['room_name'] = $newSessionData['room_name'];
            $this->sessions[$idx]['room_location'] = $newSessionData['room_location'];
            $this->sessions[$idx]['trainer'] = $this->trainer['id'] ? [
                'id' => $this->trainer['id'],
                'name' => $this->trainer['name']
            ] : null;

            $updatedSessionPayload = [
                'id' => $this->sessions[$idx]['id'],
                'day_number' => $this->sessions[$idx]['day_number'],
                'room_name' => $this->sessions[$idx]['room_name'],
                'room_location' => $this->sessions[$idx]['room_location'],
                'trainer' => $this->sessions[$idx]['trainer'],
            ];
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

        // Emit minimal update payload so calendar can update locally
        $this->dispatch('training-updated', [
            'id' => $this->selectedEvent['id'],
            'name' => $this->selectedEvent['name'],
            'start_date' => $updateData['start_date'] ?? $this->selectedEvent['start_date'],
            'end_date' => $updateData['end_date'] ?? $this->selectedEvent['end_date'],
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
}
