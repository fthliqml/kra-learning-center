<?php

namespace App\Livewire\Components\Training;

use App\Models\User;
use App\Models\Trainer;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AddTrainingModal extends Component
{
    public $showModal = false;
    public $activeTab = 'training'; // training or session

    // Form fields
    public $training_name = '';
    public $date = '';
    public $trainer = '';

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
        'nama_training' => 'required|min:3',
        'date' => 'required|date',
        'trainer' => 'required|exists:trainer,id',
        'instruktur_internal' => 'required_if:tipe_instruktur,internal',
        'instruktur_eksternal' => 'required_if:tipe_instruktur,eksternal',
        'selected_room' => 'required',
        'session_instructor' => 'required',
        'participants' => 'required|array|min:1', // Array of IDs
        'participants.*' => 'exists:users,id', // Each ID must exist,
    ];

    protected $messages = [
        'nama_training.required' => 'Training name is required.',
        'nama_training.min' => 'Training name must be at least 3 characters.',
        'date.required' => 'Date is required.',
        'trainer.required' => 'Trainer must be selected.',
        'trainer.exists' => 'Trainer not found.',
        'instruktur_internal.required_if' => 'Internal instructor must be selected.',
        'instruktur_eksternal.required_if' => 'External instructor name is required.',
        'selected_room.required' => 'Room selection is required.',
        'session_instructor.required' => 'Session instructor is required.',
        'participants.*.required' => 'Participant name is required.',
        'participants.*.min' => 'Participant name must be at least 2 characters.',
    ];

    protected $listeners = [
        'open-add-training-modal' => 'openModalWithDate'
    ];

    public function openModalWithDate($data)
    {
        $this->resetForm();
        $this->date = $data['date'];
        $this->showModal = true;

    }

    public function mount()
    {
        $this->usersSearchable = collect([]);
        $this->userSearch();
        $this->trainersSearchable = collect([]);
        $this->trainerSearch();
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->activeTab = 'training';
        $this->resetForm();
    }

    public function closeModal()
    {
        dump($this->room, $this->trainer);
        $this->showModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function resetForm()
    {
        $this->training_name = '';
        $this->date = '';
        $this->activeTab = 'training';
        $this->trainer = '';
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
        if (!empty($this->trainer)) {
            $selected = Trainer::with('user')
                ->where('id', $this->trainer)
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

        // Simulate saving data
        $trainingData = [
            'nama_training' => $this->nama_training,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'trainer_id' => $this->trainer,
            'tipe_instruktur' => $this->tipe_instruktur,
            'instruktur' => $this->tipe_instruktur === 'internal' ? $this->instruktur_internal : $this->instruktur_eksternal,
            'session_type' => $this->session_type,
            'selected_room' => $this->selected_room,
            'session_instructor' => $this->session_instructor,
            'sessions' => $this->sessions,
            'participants' => array_filter($this->participants), // Remove empty participants
        ];

        // Here you would save to database
        // Training::create($trainingData);

        session()->flash('message', 'Training successfully created!');
        $this->closeModal();
        $this->emit('trainingCreated');
    }

    public function render()
    {
        return view('components.training.add-training-modal');
    }
}
