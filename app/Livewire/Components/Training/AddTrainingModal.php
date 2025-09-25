<?php

namespace App\Livewire\Components\Training;

use App\Models\TrainingSession;
use App\Models\User;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use Livewire\Component;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Mary\Traits\Toast;

class AddTrainingModal extends Component
{
    use Toast;
    public $showModal = false;
    public $activeTab = 'training'; // training or session

    // Form fields
    public $training_name = '';
    public $date = '';
    public $start_time = '';
    public $end_time = '';

    // id trainer
    public $trainerId = null;

    // Room
    public $room = [
        "name" => "",
        "location" => "",
    ];

    // Participants (id of users)
    public $participants = [];

    public Collection $usersSearchable;
    public Collection $trainersSearchable;

    protected $rules = [
        'training_name' => 'required|string|min:3',
        'date' => 'required|string|min:10',
        'trainerId' => 'required|integer|exists:trainer,id',
        'room.name' => 'required|string|max:100',
        'room.location' => 'required|string|max:150',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
        'participants' => 'required|array|min:1',
        'participants.*' => 'integer|exists:users,id',
    ];

    protected $messages = [
        'training_name.required' => 'Training name is required.',
        'training_name.min' => 'Training name must be at least 3 characters.',
        'date.required' => 'Training date range is required.',
        'date.min' => 'Training date range format is invalid.',
        'room.name' => 'Room name is required',
        'room.location' => 'Room location is required',
        'trainerId.required' => 'Trainer must be selected.',
        'trainerId.exists' => 'Selected trainer does not exist.',
        'start_time.required' => 'Start time is required.',
        'start_time.date_format' => 'Start time must be in HH:MM format.',
        'end_time.required' => 'End time is required.',
        'end_time.date_format' => 'End time must be in HH:MM format.',
        'end_time.after' => 'End time must be after start time.',
        'participants.required' => 'At least one participant must be selected.',
        'participants.array' => 'Participants must be an array of user IDs.',
        'participants.min' => 'Select at least one participant.',
        'participants.*.exists' => 'One or more selected participants are invalid.',
    ];

    protected $listeners = [
        'open-add-training-modal' => 'openModalWithDate'
    ];

    public function mount()
    {
        $this->usersSearchable = collect([]);
        $this->userSearch();
        $this->trainersSearchable = collect([]);
        $this->trainerSearch();
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

    public function openModalWithDate($data)
    {
        $this->resetForm();
        $this->date = $data['date'];
        $this->showModal = true;

    }

    public function openModal()
    {
        $this->showModal = true;
        $this->activeTab = 'training';
        $this->resetForm();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function resetForm()
    {
        $this->training_name = '';
        $this->date = '';
        $this->activeTab = 'training';
        $this->trainerId = null;
        $this->room = [
            "name" => "",
            "location" => "",
        ];
        $this->participants = [];
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
    }

    public function userSearch(string $value = '')
    {
        // Always include selected options first
        $selectedOptions = collect([]);
        if (!empty($this->participants) && $this->participants !== ['']) {
            $selectedOptions = User::whereIn('id', $this->participants)->get();
        }

        // Search results
        $searchResults = User::where('name', 'like', "%{$value}%")
            ->limit(10)
            ->get();

        // Merge search results with selected options (selected options persist)
        $this->usersSearchable = $searchResults->merge($selectedOptions)
            ->unique('id') // Remove duplicates
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            });
    }

    public function trainerSearch(string $value = ''): void
    {
        // Include selected trainer if any
        $selected = collect([]);
        if (!empty($this->trainerId)) {
            $selected = Trainer::with('user')
                ->where('id', $this->trainerId)
                ->get();
        }

        if (strlen($value) < 2) {
            $this->trainersSearchable = $selected->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name ?: ($trainer->user?->name ?? 'Unknown'),
                ];
            });
            return;
        }

        $results = Trainer::with('user')
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%")
                    ->orWhereHas('user', function ($uq) use ($value) {
                        $uq->where('name', 'like', "%{$value}%");
                    });
            })
            ->limit(10)
            ->get();

        $this->trainersSearchable = $results->merge($selected)
            ->unique('id')
            ->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name ?: ($trainer->user?->name ?? 'Unknown'),
                ];
            });
    }

    public function saveTraining()
    {
        $this->validate();

        $range = $this->parseDateRange($this->date);

        $startDate = $range['start'];
        $endDate = $range['end'];

        // Extra manual validation to ensure chronological order (after validation rule)
        if ($this->start_time && $this->end_time) {
            $startTime = Carbon::createFromFormat('H:i', $this->start_time);
            $endTime = Carbon::createFromFormat('H:i', $this->end_time);
            if ($endTime->lessThanOrEqualTo($startTime)) {
                $this->addError('end_time', 'End time must be later than start time.');
                return;
            }
        }

        $training = Training::create([
            "name" => $this->training_name,
            "type" => "in",
            "start_date" => $startDate,
            "end_date" => $endDate,
        ]);

        $sessions = [];
        if ($startDate && $endDate) {
            $period = CarbonPeriod::create($startDate, $endDate);
            $day = 1;
            foreach ($period as $dateObj) {
                $sessions[] = TrainingSession::create([
                    'training_id' => $training->id,
                    'day_number' => $day,
                    'date' => $dateObj->format('Y-m-d'),
                    'trainer_id' => $this->trainerId,
                    'room_name' => $this->room['name'],
                    'room_location' => $this->room['location'],
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                ]);
                $day++;
            }
        }

        foreach ($this->participants as $participantId) {
            TrainingAssessment::create(["training_id" => $training->id, "employee_id" => $participantId]);
        }

        foreach ($sessions as $session) {
            foreach ($this->participants as $participantId) {
                TrainingAttendance::create([
                    'session_id' => $session->id,
                    'employee_id' => $participantId,
                    'notes' => null,
                    'recorded_at' => Carbon::now(),
                ]);
            }
        }

        $this->success('Training data created successfully!', position: 'toast-top toast-center');

        $this->dispatch('training-created');

        $this->closeModal();
    }

    public function render()
    {
        return view('components.training.add-training-modal');
    }
}
