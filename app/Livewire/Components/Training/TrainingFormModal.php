<?php


namespace App\Livewire\Components\Training;

use App\Models\Course;
use App\Http\Requests\TrainingFormRequest;
use App\Models\SurveyResponse;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use App\Models\TrainingSurvey;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class TrainingFormModal extends Component
{
  use Toast;
  public $showModal = false;
  public $activeTab = 'training'; // training or session

  // Edit mode flags
  public bool $isEdit = false;
  public ?int $trainingId = null; // current training being edited

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

  // Original type (from DB) for edit scenario to detect transitions to K-LEARN
  public ?string $originalTrainingType = null;
  // Confirmation dialog state when changing to K-LEARN
  public bool $showTypeChangeConfirm = false;
  public ?string $pendingTrainingType = null;


  protected $listeners = [
    'open-add-training-modal' => 'openModalWithDate',
    'schedule-month-context' => 'setDefaultMonth',
    'open-training-form-edit' => 'openEdit'
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
    // Include group_comp so we can auto-sync training group_comp for K-LEARN
    $this->courseOptions = Course::select('id', 'title', 'group_comp')->orderBy('title')->get()
      ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'group_comp' => $c->group_comp])->toArray();
  }

  public function updatedCourseId($value): void
  {
    if ($this->training_type === 'K-LEARN' && $value) {
      $course = collect($this->courseOptions)->firstWhere('id', (int) $value);
      $this->training_name = $course['title'] ?? '';
      // Auto-sync group competency with selected course
      if (!empty($course['group_comp'])) {
        $this->group_comp = $course['group_comp'];
      } else {
        // Fallback: query directly if options list missing group_comp
        $this->group_comp = (string) (Course::where('id', (int) $value)->value('group_comp') ?? $this->group_comp);
      }
    }
  }

  public function updatedTrainingType($value): void
  {
    $this->resetValidation();
    // If editing and moving from a non K-LEARN -> K-LEARN, require confirmation
    if ($this->isEdit && $value === 'K-LEARN' && $this->originalTrainingType !== 'K-LEARN') {
      $this->pendingTrainingType = 'K-LEARN';
      $this->showTypeChangeConfirm = true;
      // Revert visible value until user confirms (Livewire already set it, so set back):
      $this->training_type = $this->originalTrainingType;
      return;
    }
    // Normal immediate transitions (create mode or switching away from K-LEARN)
    if ($value === 'K-LEARN') {
      $this->applyKLearnSwitch();
    } else {
      // Leaving K-LEARN
      $this->course_id = null;
    }
  }

  private function applyKLearnSwitch(): void
  {
    $this->training_type = 'K-LEARN';
    $this->training_name = '';
    $this->course_id = null;
    $this->trainerId = null;
    $this->room = ['name' => $this->room['name'] ?? '', 'location' => $this->room['location'] ?? ''];
    $this->start_time = '';
    $this->end_time = '';
    $this->loadCourseOptions();
  }

  public function confirmTypeChange(): void
  {
    if ($this->pendingTrainingType === 'K-LEARN') {
      $this->applyKLearnSwitch();
      $this->showTypeChangeConfirm = false;
      $this->pendingTrainingType = null;
      // Note: actual attendance deletion occurs on saveTraining when type persisted.
    }
  }

  public function cancelTypeChange(): void
  {
    $this->showTypeChangeConfirm = false;
    $this->pendingTrainingType = null;
    // Restore visual type to original if needed
    if ($this->isEdit && $this->originalTrainingType) {
      $this->training_type = $this->originalTrainingType;
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
    $this->isEdit = false;
    $this->trainingId = null;
    $this->originalTrainingType = null;
    $this->activeTab = 'training';
    $this->showTypeChangeConfirm = false;
    $this->pendingTrainingType = null;

    // Only set date if explicitly provided; otherwise leave blank for safety.
    if (!empty($data['date'])) {
      $this->date = $data['date'];
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
    // Reset form; do NOT set any default date to force explicit user selection
    $this->resetForm();
    $this->isEdit = false;
    $this->trainingId = null;
    $this->activeTab = 'training';
    $this->showModal = true;
  }

  public function closeModal()
  {
    $this->showModal = false;
    $this->resetForm();
    $this->resetErrorBag();
    $this->isEdit = false;
    $this->trainingId = null;
  }

  public function resetForm()
  {
    $this->training_name = '';
    $this->training_type = 'IN';
    $this->group_comp = 'BMC';
    $this->date = '';
    $this->start_time = '';
    $this->end_time = '';
    $this->course_id = null;
    $this->activeTab = 'training';
    $this->trainerId = null;
    $this->room = [
      "name" => "",
      "location" => "",
    ];
    $this->participants = [];
    $this->originalTrainingType = null;
    $this->showTypeChangeConfirm = false;
    $this->pendingTrainingType = null;

    // Reset searchable collections
    $this->usersSearchable = collect([]);
    $this->trainersSearchable = collect([]);

    // Refresh searchable lists with fresh data
    $this->userSearch();
    $this->trainerSearch();

    $this->resetErrorBag();
    $this->resetValidation();
  }

  /**
   * Open the modal in edit mode for an existing training.
   */
  public function openEdit($payload): void
  {
    // Payload may be an ID (int/string) or an array containing ['id'=>...] from action-choice modal
    $id = null;
    if (is_array($payload)) {
      $id = $payload['id'] ?? null;
    } elseif (is_numeric($payload)) {
      $id = (int) $payload;
    }
    if (!$id) {
      $this->error('Invalid training reference.');
      return;
    }
    $this->resetForm();
    $this->isEdit = true;
    $this->trainingId = (int) $id;

    $training = Training::with([
      'sessions' => function ($q) {
        $q->orderBy('day_number');
      },
      'assessments',
      'course'
    ])->find($this->trainingId);

    if (!$training) {
      $this->error('Training not found');
      return;
    }

    // Populate base fields
    $this->training_type = $training->type; // Locked in UI while editing
    $this->originalTrainingType = $training->type;
    $this->group_comp = $training->group_comp;
    $this->course_id = $training->course_id;
    if ($training->type !== 'K-LEARN') {
      $this->training_name = $training->name;
    } else {
      $this->training_name = $training->course?->title ?? $training->name;
    }

    // Date range (keep existing).
    if ($training->start_date && $training->end_date) {
      $start = $training->start_date instanceof Carbon ? $training->start_date->toDateString() : Carbon::parse($training->start_date)->toDateString();
      $end = $training->end_date instanceof Carbon ? $training->end_date->toDateString() : Carbon::parse($training->end_date)->toDateString();
      $this->date = $start . ' to ' . $end;
    }

    // Sessions: use first session as representative for trainer, room, times.
    $firstSession = $training->sessions->first();
    if ($firstSession) {
      $this->trainerId = $firstSession->trainer_id;
      $this->room = [
        'name' => $firstSession->room_name ?? '',
        'location' => $firstSession->room_location ?? '',
      ];
      $this->start_time = $firstSession->start_time ? Carbon::parse($firstSession->start_time)->format('H:i') : '';
      $this->end_time = $firstSession->end_time ? Carbon::parse($firstSession->end_time)->format('H:i') : '';
    }

    // Participants via TrainingAssessment
    $this->participants = $training->assessments->pluck('employee_id')->map(fn($id) => (int) $id)->toArray();

    // Refresh searchable lists so that selected participants/trainers appear
    $this->userSearch();
    $this->trainerSearch();

    $this->activeTab = 'training';
    $this->showModal = true;
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
    // Validasi menggunakan FormRequest
    $request = new TrainingFormRequest();
    $request->merge($this->getValidationData());


    $validator = Validator::make(
      $request->all(),
      $request->rules(),
      $request->messages()
    );
    if ($validator->fails()) {
      $all = collect($validator->errors()->all());
      if ($all->isNotEmpty()) {
        $lines = $all->take(8)->map(fn($m) => '• ' . $m)->implode('<br>');
        if ($all->count() > 8) {
          $lines .= '<br>• (' . ($all->count() - 8) . ' more ...)';
        }
        $this->error($lines, position: 'toast-top toast-center');
      }
      throw new \Illuminate\Validation\ValidationException($validator);
    }

    $range = $this->parseDateRange($this->date);
    $startDate = $range['start'];
    $endDate = $range['end'];

    if ($this->start_time && $this->end_time) {
      $startTime = Carbon::createFromFormat('H:i', $this->start_time);
      $endTime = Carbon::createFromFormat('H:i', $this->end_time);
      if ($endTime->lessThanOrEqualTo($startTime)) {
        $this->addError('end_time', 'End time must be later than start time.');
        $this->error('End time must be later than start time.', position: 'toast-top toast-center');
        return;
      }
    }

    $courseTitle = null;
    if ($this->training_type === 'K-LEARN') {
      $course = Course::find($this->course_id);
      $courseTitle = $course?->title;
    }

    if ($this->isEdit && $this->trainingId) {
      $this->updateTraining($courseTitle, $startDate, $endDate);
      return;
    }

    $groupToPersist = $this->group_comp;
    if ($this->training_type === 'K-LEARN' && $this->course_id) {
      $syncedGroup = Course::where('id', $this->course_id)->value('group_comp');
      if ($syncedGroup) {
        $this->group_comp = $syncedGroup;
        $groupToPersist = $syncedGroup;
      }
    }

    $training = Training::create([
      'name' => $this->training_type === 'K-LEARN' ? ($courseTitle ?? 'K-Learn') : $this->training_name,
      'type' => $this->training_type,
      'group_comp' => $groupToPersist,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'course_id' => $this->training_type === 'K-LEARN' ? $this->course_id : null,
    ]);

    $surveys = $this->createSurveysForTraining($training);
    $this->createSurveyResponsesForParticipants($surveys, $this->participants);

    $sessions = $this->createSessionsForTraining($training, $startDate, $endDate);

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

  /**
   * Ambil data validasi dari property Livewire
   */
  private function getValidationData(): array
  {
    return [
      'training_name' => $this->training_name,
      'training_type' => $this->training_type,
      'group_comp' => $this->group_comp,
      'date' => $this->date,
      'trainerId' => $this->trainerId,
      'course_id' => $this->course_id,
      'room' => $this->room,
      'start_time' => $this->start_time,
      'end_time' => $this->end_time,
      'participants' => $this->participants,
    ];
  }

  /**
   * Update existing training (edit mode)
   */
  private function updateTraining($courseTitle, $startDate, $endDate): void
  {
    DB::transaction(function () use ($courseTitle, $startDate, $endDate) {
      $training = Training::with('sessions')->find($this->trainingId);
      if (!$training) {
        $this->error('Training not found');
        DB::rollBack();
        return;
      }

      $originalType = $training->type;
      $originalStart = $training->start_date ? Carbon::parse($training->start_date)->toDateString() : null;
      $originalEnd = $training->end_date ? Carbon::parse($training->end_date)->toDateString() : null;

      // Update main training fields
      $this->updateTrainingFields($training, $courseTitle, $startDate, $endDate);

      // Update sessions if needed
      $dateChanged = ($originalStart !== $startDate) || ($originalEnd !== $endDate);
      $typeChanged = $originalType !== $this->training_type;
      if ($dateChanged || $typeChanged) {
        $this->rebuildSessions($training, $startDate, $endDate, $originalType);
      } else {
        $this->updateSessionFields($training);
      }

      // Update participants and survey responses
      $this->updateParticipantsAndSurveyResponses($training);

      // Update attendance
      $this->updateAttendance($training);

      $this->success('Training updated successfully!', position: 'toast-top toast-center');
      $this->dispatch('training-updated', id: $training->id);
    });
    $this->closeModal();
  }

  /**
   * Update main training fields (type, name, group, dates)
   */
  private function updateTrainingFields($training, $courseTitle, $startDate, $endDate): void
  {
    $training->type = $this->training_type;
    if ($this->training_type === 'K-LEARN') {
      $training->course_id = $this->course_id;
      $training->name = $courseTitle ?? $training->name;
    } else {
      $training->course_id = null;
      $training->name = $this->training_name;
    }
    if ($this->training_type === 'K-LEARN') {
      $syncedGroup = Course::where('id', $this->course_id)->value('group_comp');
      if ($syncedGroup) {
        $this->group_comp = $syncedGroup;
        $training->group_comp = $syncedGroup;
      } else {
        $training->group_comp = $this->group_comp;
      }
    } else {
      $training->group_comp = $this->group_comp;
    }
    $training->start_date = $startDate;
    $training->end_date = $endDate;
    $training->save();
  }

  /**
   * Update session fields if not rebuilding sessions
   */
  private function updateSessionFields($training): void
  {
    foreach ($training->sessions as $session) {
      if ($training->type === 'K-LEARN') {
        $session->room_name = $this->room['name'] ?: null;
        $session->room_location = $this->room['location'] ?: null;
        $session->trainer_id = null;
        $session->start_time = null;
        $session->end_time = null;
      } else {
        $session->trainer_id = $this->trainerId;
        $session->room_name = $this->room['name'];
        $session->room_location = $this->room['location'];
        $session->start_time = $this->start_time;
        $session->end_time = $this->end_time;
      }
      $session->save();
    }
  }

  /**
   * Update participants (assessments) and survey responses for this training
   */
  private function updateParticipantsAndSurveyResponses($training): void
  {
    $existingParticipantIds = $training->assessments()->pluck('employee_id')->map(fn($id) => (int) $id)->toArray();
    $newParticipantIds = array_map('intval', $this->participants);
    $toAdd = array_diff($newParticipantIds, $existingParticipantIds);
    $toRemove = array_diff($existingParticipantIds, $newParticipantIds);

    foreach ($toAdd as $empId) {
      TrainingAssessment::create(["training_id" => $training->id, "employee_id" => $empId]);
    }
    if (!empty($toRemove)) {
      TrainingAssessment::where('training_id', $training->id)
        ->whereIn('employee_id', $toRemove)
        ->delete();
    }

    // Update SurveyResponse for all surveys of this training
    $surveys = TrainingSurvey::where('training_id', $training->id)->get();
    // Add SurveyResponse for new participants
    foreach ($toAdd as $empId) {
      foreach ($surveys as $survey) {
        SurveyResponse::firstOrCreate([
          'survey_id' => $survey->id,
          'employee_id' => $empId,
        ]);
      }
    }
    // Remove SurveyResponse for removed participants
    if (!empty($toRemove)) {
      foreach ($surveys as $survey) {
        SurveyResponse::where('survey_id', $survey->id)
          ->whereIn('employee_id', $toRemove)
          ->delete();
      }
    }
  }

  /**
   * Update attendance for all sessions and participants
   */
  private function updateAttendance($training): void
  {
    $newParticipantIds = array_map('intval', $this->participants);
    $sessionIds = $training->sessions()->pluck('id');
    TrainingAttendance::whereIn('session_id', $sessionIds)->delete();
    if ($training->type !== 'K-LEARN') {
      foreach ($training->sessions as $session) {
        foreach ($newParticipantIds as $pid) {
          TrainingAttendance::create([
            'session_id' => $session->id,
            'employee_id' => $pid,
            'notes' => null,
            'recorded_at' => Carbon::now(),
          ]);
        }
      }
    }
  }

  /**
   * Create surveys for each level (1,2,3) for a training
   */
  private function createSurveysForTraining($training): array
  {
    $surveys = [];
    for ($level = 1; $level <= 3; $level++) {
      $surveys[$level] = TrainingSurvey::create([
        'training_id' => $training->id,
        'level' => $level,
        'status' => TrainingSurvey::STATUS_DRAFT,
      ]);
    }
    return $surveys;
  }

  /**
   * Create survey responses for each participant for each survey
   */
  private function createSurveyResponsesForParticipants(array $surveys, array $participants): void
  {
    if (empty($participants) || empty($surveys)) {
      return;
    }
    foreach ($participants as $participantId) {
      foreach ($surveys as $survey) {
        SurveyResponse::firstOrCreate([
          'survey_id' => $survey->id,
          'employee_id' => $participantId,
        ]);
      }
    }
  }

  /**
   * Create sessions for a training between start and end date
   */
  private function createSessionsForTraining($training, $startDate, $endDate): array
  {
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
    return $sessions;
  }

  /**
   * Rebuild sessions when date range or type changes. Removes old sessions and recreates sequential days.
   * Keeps semantics:
   *  - K-LEARN: no trainer / times (null), optional room.
   *  - Non K-LEARN: apply trainer/time/room values from form.
   */
  private function rebuildSessions(Training $training, ?string $startDate, ?string $endDate, string $previousType): void
  {
    // Delete all old sessions (cascade attendances already handled outside)
    $training->sessions()->delete();
    if (!$startDate || !$endDate)
      return;
    $period = CarbonPeriod::create($startDate, $endDate);
    $day = 1;
    foreach ($period as $dateObj) {
      TrainingSession::create([
        'training_id' => $training->id,
        'day_number' => $day,
        'date' => $dateObj->format('Y-m-d'),
        'trainer_id' => $training->type === 'K-LEARN' ? null : $this->trainerId,
        'room_name' => $training->type === 'K-LEARN' ? ($this->room['name'] ?: null) : $this->room['name'],
        'room_location' => $training->type === 'K-LEARN' ? ($this->room['location'] ?: null) : $this->room['location'],
        'start_time' => $training->type === 'K-LEARN' ? null : $this->start_time,
        'end_time' => $training->type === 'K-LEARN' ? null : $this->end_time,
      ]);
      $day++;
    }
    // Reload relation for subsequent logic
    $training->load('sessions');
  }

  public function render()
  {
    return view('components.training.training-form-modal');
  }

}
