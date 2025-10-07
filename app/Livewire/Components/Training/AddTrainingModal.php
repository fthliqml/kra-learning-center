<?php

namespace App\Livewire\Components\Training;

use App\Models\TrainingSession;
use App\Models\Course;
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
    public $training_type = 'IN';
    public $group_comp = 'BMC';
    public $date = '';
    public $start_time = '';
    public $end_time = '';
    public $course_id = null; // Only for K-LEARN type

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

    public $trainingTypeOptions = [
        ['id' => 'IN', 'name' => 'In-House'],
        ['id' => 'OUT', 'name' => 'Out-House'],
        ['id' => 'K-LEARN', 'name' => 'K-Learn']
    ];

    public $groupCompOptions = [
        ['id' => 'BMC', 'name' => 'BMC'],
        ['id' => 'BC', 'name' => 'BC'],
        ['id' => 'MMP', 'name' => 'MMP'],
        ['id' => 'LC', 'name' => 'LC'],
        ['id' => 'MDP', 'name' => 'MDP'],
        ['id' => 'TOC', 'name' => 'TOC'],
    ];

    /**
     * Dynamic rules: adjust when K-LEARN (course based) vs regular training.
     */
    public function rules(): array
    {
        if ($this->training_type === 'K-LEARN') {
            return [
                'course_id' => 'required|integer|exists:courses,id',
                'training_type' => 'required',
                'group_comp' => 'required',
                'date' => 'required|string|min:10',
                'room.name' => 'nullable|string|max:100',
                'room.location' => 'nullable|string|max:150',
                // Trainer & times optional / ignored
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'participants' => 'required|array|min:1',
                'participants.*' => 'integer|exists:users,id',
            ];
        }
        return [
            'training_name' => 'required|string|min:3',
            'training_type' => 'required',
            'group_comp' => 'required',
            'date' => 'required|string|min:10',
            'trainerId' => 'required|integer|exists:trainer,id',
            'room.name' => 'required|string|max:100',
            'room.location' => 'required|string|max:150',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer|exists:users,id',
        ];
    }

    protected $messages = [
        'training_name.required' => 'Training name is required.',
        'course_id.required' => 'Course must be selected for K-Learn.',
        'training_type.required' => 'Training type is required.',
        'group_comp.required' => 'Group competency is required.',
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
        'open-add-training-modal' => 'openModalWithDate',
        'schedule-month-context' => 'setDefaultMonth'
    ];

    public function mount()
    {
        $this->usersSearchable = collect([]);
        $this->userSearch();
        $this->trainersSearchable = collect([]);
        $this->trainerSearch();
        $this->loadCourseOptions();
    }

    public array $courseOptions = [];

    private function loadCourseOptions(): void
    {
        $this->courseOptions = Course::select('id', 'title')->orderBy('title')->get()
            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title])->toArray();
    }

    public function updatedCourseId($value): void
    {
        if ($this->training_type === 'K-LEARN' && $value) {
            $course = collect($this->courseOptions)->firstWhere('id', (int) $value);
            $this->training_name = $course['title'] ?? '';
        }
    }

    public function updatedTrainingType($value): void
    {
        // Clear errors & adjust when switching type
        $this->resetValidation();
        if ($value === 'K-LEARN') {
            $this->training_name = '';
            $this->course_id = null;
            $this->trainerId = null;
            $this->room = ['name' => '', 'location' => ''];
            $this->start_time = '';
            $this->end_time = '';
            $this->loadCourseOptions();
        } else {
            // Leaving K-LEARN: reset course_id
            $this->course_id = null;
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

    public function openModalWithDate($data)
    {
        $this->resetForm();
        // If caller passes a specific date, keep it; otherwise default to October 1st
        if (!empty($data['date'])) {
            $this->date = $data['date'];
        } else {
            // Use last known context month if available; fallback to October
            $year = $this->contextYear ?? Carbon::now()->year;
            $month = $this->contextMonth ?? 10;
            $first = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
            $this->date = $first . ' to ' . $first;
        }
        $this->showModal = true;

    }

    // Hold last known schedule context
    public ?int $contextYear = null;
    public ?int $contextMonth = null;

    public function setDefaultMonth(int $year, int $month): void
    {
        $this->contextYear = $year;
        $this->contextMonth = $month;
    }

    public function openModal()
    {
        // Reset form and default date picker to the current schedule month (fallback to current month)
        $this->resetForm();
        $this->activeTab = 'training';
        $year = $this->contextYear ?? Carbon::now()->year;
        $month = $this->contextMonth ?? Carbon::now()->month;
        $first = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        $this->date = $first . ' to ' . $first;
        $this->showModal = true;
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
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->date = '';
        $this->course_id = null;
        $this->activeTab = 'training';
        $this->trainerId = null;
        $this->room = [
            "name" => "",
            "location" => "",
        ];
        $this->participants = [];
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
        $this->resetErrorBag();
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

        // Determine effective name & course reference
        $courseTitle = null;
        if ($this->training_type === 'K-LEARN') {
            $course = Course::find($this->course_id);
            $courseTitle = $course?->title;
        }

        $training = Training::create([
            'name' => $this->training_type === 'K-LEARN' ? ($courseTitle ?? 'K-Learn') : $this->training_name,
            'type' => $this->training_type,
            'group_comp' => $this->group_comp,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'course_id' => $this->training_type === 'K-LEARN' ? $this->course_id : null,
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
                    'trainer_id' => $this->training_type === 'K-LEARN' ? null : $this->trainerId,
                    'room_name' => $this->training_type === 'K-LEARN' ? ($this->room['name'] ?: null) : $this->room['name'],
                    'room_location' => $this->training_type === 'K-LEARN' ? ($this->room['location'] ?: null) : $this->room['location'],
                    'start_time' => $this->training_type === 'K-LEARN' ? null : $this->start_time,
                    'end_time' => $this->training_type === 'K-LEARN' ? null : $this->end_time,
                ]);
                $day++;
            }
        }

        foreach ($this->participants as $participantId) {
            TrainingAssessment::create(["training_id" => $training->id, "employee_id" => $participantId]);
        }

        if ($this->training_type !== 'K-LEARN') {
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
