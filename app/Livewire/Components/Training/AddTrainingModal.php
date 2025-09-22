<?php

namespace App\Livewire\Components\Training;

use App\Models\User;
use Livewire\Component;
use Carbon\Carbon;

class AddTrainingModal extends Component
{
    public $showModal = false;
    public $activeTab = 'training'; // training or session

    // Form fields
    public $nama_training = '';
    public $date = '';
    public $tipe_instruktur = 'internal'; // internal atau eksternal
    public $instruktur_internal = '';
    public $instruktur_eksternal = '';

    // Session configuration
    public $session_type = 'same_room'; // same_room atau different_room
    public $selected_room = '';
    public $selected_room_location = '';
    public $session_instructor = '';

    // Sessions data
    public $sessions = [];

    // Participants - now as simple array of names
    public $participants = [''];
    public $participant_suggestions = [];
    public $show_suggestions = [];

    // Static data
    public $internal_instructors = [
        ['id' => 1, 'name' => 'Dr. Ahmad Fauzi'],
        ['id' => 2, 'name' => 'Ir. Siti Nurhaliza, M.T.'],
        ['id' => 3, 'name' => 'Prof. Budi Santoso, Ph.D.'],
        ['id' => 4, 'name' => 'Dr. Maya Sari, S.Kom., M.Kom.'],
    ];

    public $available_rooms = [
        ['id' => 1, 'name' => 'Meeting Room A', 'location' => 'Wakatobi - Office New'],
        ['id' => 2, 'name' => 'Meeting Room B', 'location' => 'Wakatobi - Office New'],
        ['id' => 3, 'name' => 'Training Room 1', 'location' => 'Komodo - Office Main'],
        ['id' => 4, 'name' => 'Training Room 2', 'location' => 'Komodo - Office Main'],
        ['id' => 5, 'name' => 'Conference Hall', 'location' => 'Bromo - Office Central'],
    ];

    public $available_participants = [
        'John Doe - IT Department',
        'Jane Smith - HR Department',
        'Bob Wilson - Finance Department',
        'Alice Brown - Marketing Department',
        'Charlie Davis - Operations Department',
        'Diana Johnson - Legal Department',
        'Edward Lee - Sales Department',
        'Fiona Chen - Research Department',
        'Michael Zhang - Engineering Department',
        'Sarah Connor - Security Department',
        'David Park - Design Department',
        'Lisa Wang - Data Analytics Department',
    ];

    protected $rules = [
        'nama_training' => 'required|min:3',
        'date' => 'required|date',
        'instruktur_internal' => 'required_if:tipe_instruktur,internal',
        'instruktur_eksternal' => 'required_if:tipe_instruktur,eksternal',
        'selected_room' => 'required',
        'session_instructor' => 'required',
        'participants.*' => 'required|min:2',
    ];

    protected $messages = [
        'nama_training.required' => 'Nama training wajib diisi.',
        'nama_training.min' => 'Nama training minimal 3 karakter.',
        'date.required' => 'Tanggal wajib diisi.',
        'instruktur_internal.required_if' => 'Instruktur internal wajib dipilih.',
        'instruktur_eksternal.required_if' => 'Nama instruktur eksternal wajib diisi.',
        'selected_room.required' => 'Ruangan wajib dipilih.',
        'session_instructor.required' => 'Instruktur sesi wajib diisi.',
        'participants.*.required' => 'Nama peserta wajib diisi.',
        'participants.*.min' => 'Nama peserta minimal 2 karakter.',
    ];

    public function mount()
    {
        // $this->generateSessions();
        $this->show_suggestions = [false]; // Initialize suggestions visibility
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
        $this->nama_training = '';
        $this->date = '';
        $this->tipe_instruktur = 'internal';
        $this->instruktur_internal = '';
        $this->instruktur_eksternal = '';
        $this->session_type = 'same_room';
        $this->selected_room = '';
        $this->selected_room_location = '';
        $this->session_instructor = '';
        $this->sessions = [];
        $this->participants = [''];
        $this->participant_suggestions = [];
        $this->show_suggestions = [false];
    }

    public function updatedStartDate()
    {
        $this->generateSessions();
    }

    public function updatedEndDate()
    {
        $this->generateSessions();
    }

    public function updatedSessionType()
    {
        $this->generateSessions();
    }

    public function generateSessions()
    {
        if (!$this->start_date || !$this->end_date) {
            $this->sessions = [];
            return;
        }

        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $this->sessions = [];

        if ($this->session_type === 'same_room') {
            $this->sessions[] = [
                'date_range' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
                'room' => 'Ruang Meeting A'
            ];
        } else {
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $this->sessions[] = [
                    'date' => $currentDate->format('d/m/Y'),
                    'room' => 'Ruang Meeting ' . chr(65 + count($this->sessions)) // A, B, C, dst.
                ];
                $currentDate->addDay();
            }
        }
    }

    public function userSearch($query)
    {
        return User::where('name', 'like', "%$query%")
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
            ])
            ->toArray();
    }

    public function saveTraining()
    {
        $this->validate();

        // Simulate saving data
        $trainingData = [
            'nama_training' => $this->nama_training,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
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

        session()->flash('message', 'Training berhasil dibuat!');
        $this->closeModal();
        $this->emit('trainingCreated');
    }

    public function render()
    {
        return view('components.training.add-training-modal');
    }
}
