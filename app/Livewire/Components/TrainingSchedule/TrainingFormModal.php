<?php


namespace App\Livewire\Components\TrainingSchedule;

use App\Models\Course;
use App\Http\Requests\TrainingFormRequest;
use App\Models\Competency;
use App\Models\SurveyResponse;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingModule;
use App\Models\TrainingSession;
use App\Models\TrainingSurvey;
use App\Models\User;
use App\Services\Training\TrainingPersistService;
use App\Services\Training\SessionSyncService;
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

    // Training name edit tracking (mainly for OUT-house auto-fill behavior)
    public bool $trainingNameManuallyEdited = false;
    private bool $trainingNameWasAutoFilled = false;
    private bool $isSettingTrainingNameProgrammatically = false;
    public $selected_module_id = null; // For In-House type: selected training module
    public $date = '';
    public $start_time = '';
    public $end_time = '';
    public $course_id = null; // Only for LMS type

    // Only for OUT type
    public $competency_id = null;

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
        ['id' => 'LMS', 'name' => 'LMS'],
        ['id' => 'BLENDED', 'name' => 'Blended'],
    ];

    public $groupCompOptions = [
        ['id' => 'BMC', 'name' => 'BMC'],
        ['id' => 'BC', 'name' => 'BC'],
        ['id' => 'MMP', 'name' => 'MMP'],
        ['id' => 'LC', 'name' => 'LC'],
        ['id' => 'MDP', 'name' => 'MDP'],
        ['id' => 'TOC', 'name' => 'TOC'],
    ];

    // Original type (from DB) for edit scenario to detect transitions to LMS
    public ?string $originalTrainingType = null;
    // Confirmation dialog state when changing to LMS
    public bool $showTypeChangeConfirm = false;
    public ?string $pendingTrainingType = null;


    protected $listeners = [
        'open-add-training-modal' => 'openModalWithDate',
        'schedule-month-context' => 'setDefaultMonth',
        'open-training-form-edit' => 'openEdit',
        'confirm-delete-training-form' => 'onConfirmDelete'
    ];

    // Performance: Track if dropdown data has been loaded
    private bool $dataLoaded = false;

    public function mount()
    {
        // PERFORMANCE: Don't load anything on mount!
        // Data will be loaded lazily when modal opens
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
    }

    /**
     * Lazy load all dropdown data - called only when modal opens
     * PUBLIC: Called from Alpine.js via $wire.loadDropdownData()
     */
    public function loadDropdownData(): void
    {
        if ($this->dataLoaded) {
            return; // Already loaded, skip
        }

        $this->loadCourseOptions();
        $this->loadTrainingModuleOptions();
        $this->loadCompetencyOptions();
        $this->userSearch();
        $this->trainerSearch();
        
        $this->dataLoaded = true;
    }

    public array $courseOptions = [];
    public array $trainingModuleOptions = [];
    public array $competencyOptions = [];

    /**
     * PERFORMANCE: Cache dropdown options for 1 hour to avoid repeated queries
     */
    private function loadCourseOptions(): void
    {
        // Use cache to avoid query every time modal opens
        $this->courseOptions = cache()->remember('training_form_course_options', 3600, function () {
            return Course::with('competency:id,type')
                ->select('id', 'title', 'competency_id')
                ->orderBy('title')
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'group_comp' => $c->competency->type ?? null])
                ->toArray();
        });
    }

    // PERFORMANCE: In-memory cache for group lookups
    private array $courseGroupCache = [];
    private array $competencyGroupCache = [];
    private array $competencyNameCache = [];

    private function getCourseGroupComp(?int $courseId): ?string
    {
        if (!$courseId) {
            return null;
        }

        // Check cache first
        if (isset($this->courseGroupCache[$courseId])) {
            return $this->courseGroupCache[$courseId];
        }

        $course = Course::with('competency:id,type')
            ->select('id', 'competency_id')
            ->find($courseId);

        $group = trim((string) ($course?->competency?->type ?? ''));
        $result = $group !== '' ? $group : null;
        
        // Cache the result
        $this->courseGroupCache[$courseId] = $result;
        
        return $result;
    }

    private function loadCompetencyOptions(?int $ensureCompetencyId = null): void
    {
        // Use cache for base competency options
        $options = cache()->remember('training_form_competency_options', 3600, function () {
            return Competency::query()
                ->select('id', 'code', 'name')
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
                ->toArray();
        });

        // Ensure selected competency is in list (for edit mode)
        if ($ensureCompetencyId) {
            $existingIds = array_column($options, 'id');
            if (!in_array($ensureCompetencyId, $existingIds, true)) {
                $selected = Competency::query()
                    ->select('id', 'code', 'name')
                    ->find($ensureCompetencyId);

                if ($selected) {
                    array_unshift($options, [
                        'id' => $selected->id,
                        'name' => trim($selected->code . ' - ' . $selected->name),
                    ]);
                }
            }
        }

        $this->competencyOptions = $options;
    }

    private function getCompetencyGroupComp(?int $competencyId): ?string
    {
        if (!$competencyId) {
            return null;
        }

        // Check cache first
        if (isset($this->competencyGroupCache[$competencyId])) {
            return $this->competencyGroupCache[$competencyId];
        }

        $competency = Competency::query()
            ->select('id', 'type')
            ->find($competencyId);

        $group = trim((string) ($competency?->type ?? ''));
        $result = $group !== '' ? $group : null;
        
        // Cache the result
        $this->competencyGroupCache[$competencyId] = $result;
        
        return $result;
    }

    private function getCompetencyTrainingName(?int $competencyId): ?string
    {
        if (!$competencyId) {
            return null;
        }

        // Check cache first
        if (isset($this->competencyNameCache[$competencyId])) {
            return $this->competencyNameCache[$competencyId];
        }

        $competency = Competency::query()
            ->select('id', 'name')
            ->find($competencyId);

        $name = trim((string) ($competency?->name ?? ''));
        $result = $name !== '' ? $name : null;
        
        // Cache the result
        $this->competencyNameCache[$competencyId] = $result;
        
        return $result;
    }

    private function parseCompetencyIdFromValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        // Option object/array from select components
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : (array) $value;
            $candidate = $arr['id'] ?? ($arr['value'] ?? null);
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }

            // Sometimes only label is provided (e.g. "CODE - Name")
            $label = $arr['name'] ?? ($arr['label'] ?? null);
            if (is_string($label)) {
                return $this->parseCompetencyIdFromValue($label);
            }
            return null;
        }

        // Scalar
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        // Try to extract code from "CODE - Name"
        $code = null;
        if (str_contains($raw, ' - ')) {
            $code = trim(explode(' - ', $raw, 2)[0]);
        }
        if ($code) {
            $id = Competency::query()->where('code', $code)->value('id');
            return $id ? (int) $id : null;
        }

        return null;
    }

    private function parseCompetencyNameFromValue(mixed $value): ?string
    {
        // If we get label like "CODE - Name", extract Name.
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : (array) $value;
            $value = $arr['name'] ?? ($arr['label'] ?? null);
        }
        if (!is_string($value)) {
            return null;
        }
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, ' - ')) {
            $name = trim(explode(' - ', $raw, 2)[1] ?? '');
            return $name !== '' ? $name : null;
        }
        return null;
    }

    private function setTrainingNameProgrammatically(?string $name, bool $autoFilled = false): void
    {
        $this->isSettingTrainingNameProgrammatically = true;
        $this->training_name = (string) ($name ?? '');
        $this->isSettingTrainingNameProgrammatically = false;

        $this->trainingNameWasAutoFilled = $autoFilled;
        if ($autoFilled) {
            $this->trainingNameManuallyEdited = false;
        }
    }

    private function loadTrainingModuleOptions(): void
    {
        // Use cache to avoid query every time modal opens
        $this->trainingModuleOptions = cache()->remember('training_form_module_options', 3600, function () {
            return TrainingModule::with('competency')
                ->orderBy('title')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'group_comp' => $m->competency?->type ?? null
                ])->toArray();
        });
    }

    public function updatedSelectedModuleId($value): void
    {
        if ($value) {
            // If training type is LMS, this is actually a course selection (x-choices binding issue)
            if ($this->training_type === 'LMS') {
                $this->course_id = (int) $value;
                $this->updatedCourseId($value);
                return;
            }

            // Normal IN-HOUSE module selection
            $module = TrainingModule::with('competency')->find((int) $value);
            if ($module) {
                $this->setTrainingNameProgrammatically($module->title, autoFilled: true);
                $this->group_comp = $module->competency?->type ?? 'BMC';
            }
        }
    }

    public function updatedCourseId($value): void
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return;
        }

        $id = null;
        $title = null;
        $group = null;

        // Handle when x-choices passes the whole option object
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : (array) $value;
            // Common key patterns from select components
            $id = isset($arr['id']) ? (int) $arr['id'] : (isset($arr['value']) ? (int) $arr['value'] : null);
            $title = $arr['title'] ?? ($arr['label'] ?? ($arr['name'] ?? null));
            $group = $arr['group_comp'] ?? ($arr['group'] ?? null);
        } else {
            // Handle scalar id (string/int)
            $id = is_numeric($value) ? (int) $value : null;
        }

        if ($id) {
            // Sync group_comp from the related competency type (courses table has no group_comp column)
            $course = Course::with('competency:id,type')
                ->select('id', 'title', 'competency_id')
                ->find($id);
            if ($course) {
                $title = $course->title;
                $group = $course->competency?->type;
            }
            $this->course_id = $id;
        }

        if ($title) {
            $this->setTrainingNameProgrammatically($title, autoFilled: true);
        }
        if ($group !== null) {
            // Mirror DB value exactly (trim only)
            $trimmed = trim((string) $group);
            $this->group_comp = $trimmed !== '' ? $trimmed : 'BMC';
        }
    }

    public function updatedTrainingName($value): void
    {
        if ($this->isSettingTrainingNameProgrammatically) {
            return;
        }

        $this->trainingNameManuallyEdited = true;
        $this->trainingNameWasAutoFilled = false;
    }

    public function updatedCompetencyId($value): void
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            $this->competency_id = null;
            $this->group_comp = 'BMC';
            return;
        }

        $id = $this->parseCompetencyIdFromValue($value);
        $this->competency_id = $id;

        $group = $this->getCompetencyGroupComp($id);
        if ($group !== null) {
            $this->group_comp = $group;
        }

        // Auto-fill training name from competency for OUT-house (editable & optional)
        if ($this->training_type === 'OUT') {
            $shouldAutofillName = trim((string) $this->training_name) === ''
                || $this->trainingNameWasAutoFilled
                || !$this->trainingNameManuallyEdited;

            if ($shouldAutofillName) {
                $name = null;
                if ($id) {
                    $name = $this->getCompetencyTrainingName($id);
                }
                if ($name === null) {
                    // Fallback if lookup fails or id can't be parsed but UI label is available
                    $name = $this->parseCompetencyNameFromValue($value);
                }
                if ($name !== null) {
                    $this->setTrainingNameProgrammatically($name, autoFilled: true);
                }
            }
        }
    }

    public function updatedTrainingType($value): void
    {
        $this->resetValidation();

        if ($value !== 'OUT') {
            $this->competency_id = null;
        }

        // Reset related fields when switching training type
        // Don't reset group_comp for LMS/BLENDED if course already selected (will be synced from course)
        if (!in_array($value, ['LMS', 'BLENDED']) || empty($this->course_id)) {
            $this->group_comp = 'BMC'; // Reset to default
        }
        $this->training_name = '';
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;

        // If editing and moving from a non LMS -> LMS, require confirmation
        if ($this->isEdit && $value === 'LMS' && $this->originalTrainingType !== 'LMS') {
            $this->pendingTrainingType = 'LMS';
            $this->showTypeChangeConfirm = true;
            // Revert visible value until user confirms (Livewire already set it, so set back):
            $this->training_type = $this->originalTrainingType;
            return;
        }
        // Normal immediate transitions (create mode or switching away from LMS)
        if ($value === 'LMS') {
            $this->applyLmsSwitch();
        } elseif ($value === 'BLENDED') {
            // BLENDED: needs Course (like LMS) + session details (like IN)
            $this->selected_module_id = null;
            $this->course_id = null;
            $this->loadCourseOptions();
        } elseif ($value === 'IN') {
            // Reset for In-House
            $this->course_id = null;
            $this->selected_module_id = null;
        } else {
            // Out-House: reset both
            $this->course_id = null;
            $this->selected_module_id = null;

            // In edit mode, keep existing competency selection.
            if (!$this->isEdit) {
                $this->competency_id = null;
            }

            $this->loadCompetencyOptions($this->parseCompetencyIdFromValue($this->competency_id));
        }
    }

    private function applyLmsSwitch(): void
    {
        $this->training_type = 'LMS';
        $this->training_name = '';
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;
        $this->course_id = null;
        $this->selected_module_id = null;
        $this->group_comp = 'BMC';
        $this->trainerId = null;
        $this->room = ['name' => $this->room['name'] ?? '', 'location' => $this->room['location'] ?? ''];
        $this->start_time = '';
        $this->end_time = '';
        $this->loadCourseOptions();
    }

    public function confirmTypeChange(): void
    {
        if ($this->pendingTrainingType === 'LMS') {
            $this->applyLmsSwitch();
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
        // PERFORMANCE: Load dropdown data lazily
        $this->loadDropdownData();
        
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
        
        // Dispatch event for calendar to hide loading overlay
        $this->dispatch('training-modal-opened');
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
        // PERFORMANCE: Load dropdown data lazily
        $this->loadDropdownData();
        
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
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->selected_module_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->course_id = null;
        $this->competency_id = null;
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
    public function requestDeleteConfirm(): void
    {
        if (!$this->trainingId) {
            return;
        }
        // Dispatch to global ConfirmDialog component
        $this->dispatch('confirm', 'Delete Confirmation', 'Are you sure you want to delete this training along with all sessions and attendance?', 'confirm-delete-training-form', $this->trainingId);
    }

    public function onConfirmDelete($id = null): void
    {
        // Ensure the confirmation corresponds to the currently opened training
        if ($id && $this->trainingId && (int) $id !== (int) $this->trainingId) {
            return;
        }
        $this->deleteTraining();
    }

    public function deleteTraining()
    {
        if (!$this->trainingId) {
            return;
        }
        
        $id = $this->trainingId;
        
        try {
            $service = app(TrainingPersistService::class);
            $deleted = $service->delete($id);
            
            if (!$deleted) {
                $this->error('Training not found.');
                $this->dispatch('confirm-done');
                return;
            }

            // Notify parent and close
            $this->dispatch('training-deleted', ['id' => $id]);
            $this->success('Training deleted.', position: 'toast-top toast-center');
            $this->showModal = false;
            $this->resetForm();
            $this->dispatch('confirm-done');
        } catch (\Throwable $e) {
            $this->error('Failed to delete training.');
            $this->dispatch('confirm-done');
        }
    }

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

        // PERFORMANCE: Reset form fields without calling userSearch/trainerSearch
        $this->resetFormFieldsOnly();
        $this->isEdit = true;
        $this->trainingId = (int) $id;

        // Load training with relations
        $training = Training::with([
            'sessions' => function ($q) {
                $q->orderBy('day_number');
            },
            'assessments',
            'course',
            'module',
            'competency'
        ])->find($this->trainingId);

        if (!$training) {
            $this->error('Training not found');
            return;
        }

        // Check if training is closed - prevent editing
        if ($training->status && strtolower($training->status) === 'done') {
            $this->error('Cannot edit a closed training. Please view details instead.');
            return;
        }

        // PERFORMANCE: Load dropdown options ONCE (with competency_id for OUT type)
        $this->loadCourseOptions();
        $this->loadTrainingModuleOptions();
        $this->loadCompetencyOptions($training->competency_id);
        $this->dataLoaded = true; // Mark as loaded to prevent loadDropdownData() from reloading

        // Populate base fields
        // Set competency_id before training_type so the training_type watcher can keep the selection in OUT-house.
        $this->competency_id = $training->competency_id;
        $this->training_type = $training->type; // Locked in UI while editing
        $this->originalTrainingType = $training->type;
        $this->group_comp = $training->type === 'OUT'
            ? ($training->competency?->type ?? $training->group_comp)
            : $training->group_comp;
        $this->course_id = $training->course_id;

        // Set selected_module_id based on training type
        if ($training->type === 'IN' && $training->module_id) {
            $this->selected_module_id = (int) $training->module_id;
        } elseif ($training->type === 'IN' && !$training->module_id) {
            // Training created before module_id feature - show info message
            $this->selected_module_id = null;
            session()->flash('info_module', 'This training was created without a module reference. Please select a training module to update it.');
        } elseif (in_array($training->type, ['LMS', 'BLENDED']) && $training->course_id) {
            // For LMS and BLENDED, selected_module_id actually stores course_id (workaround for x-choices binding)
            $this->selected_module_id = (int) $training->course_id;
        } else {
            $this->selected_module_id = null;
        }
        if (!in_array($training->type, ['LMS', 'BLENDED'])) {
            $this->setTrainingNameProgrammatically($training->name, autoFilled: false);
        } else {
            $this->setTrainingNameProgrammatically($training->course?->title ?? $training->name, autoFilled: false);
        }

        // In edit mode, treat existing name as manual to avoid unexpected overrides.
        $this->trainingNameManuallyEdited = true;
        $this->trainingNameWasAutoFilled = false;

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

        // PERFORMANCE: Load searchable lists ONCE with selected values
        $this->userSearch();
        $this->trainerSearch();

        $this->activeTab = 'training';
        $this->showModal = true;

        // Dispatch event for loading overlay to hide
        $this->dispatch('training-modal-opened');
    }
    
    /**
     * Reset form fields only without loading searchable data
     * Used by openEdit to avoid duplicate queries
     */
    private function resetFormFieldsOnly(): void
    {
        $this->training_name = '';
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->selected_module_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->course_id = null;
        $this->competency_id = null;
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
        $this->usersSearchable = collect([]);
        $this->trainersSearchable = collect([]);
        $this->resetErrorBag();
        $this->resetValidation();
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

        // PERFORMANCE: If no search value, load limited trainers (not ALL)
        if (empty($value)) {
            // Use cache for initial trainer list
            $results = cache()->remember('training_form_trainers_list', 3600, function () {
                return Trainer::with('user')
                    ->limit(20) // Only load 20 trainers initially, user can search for more
                    ->get()
                    ->map(function ($trainer) {
                        return [
                            'id' => $trainer->id,
                            'name' => $trainer->name ?: ($trainer->user?->name ?? 'Unknown'),
                        ];
                    })->toArray();
            });
            
            // Merge with selected trainer if not in list
            $merged = collect($results);
            if ($selected->isNotEmpty()) {
                $selectedMapped = $selected->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name ?: ($t->user?->name ?? 'Unknown'),
                ]);
                $merged = $merged->concat($selectedMapped)->unique('id');
            }
            
            $this->trainersSearchable = $merged;
            return;
        }

        // Search with value
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

    public function searchCourse(string $value = ''): void
    {
        if (empty($value)) {
            $this->loadCourseOptions();
            return;
        }

        $this->courseOptions = Course::with('competency:id,type')
            ->select('id', 'title', 'competency_id')
            ->where('title', 'like', "%{$value}%")
            ->orderBy('title')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'group_comp' => $c->competency->type ?? null])
            ->toArray();
    }

    public function searchTrainingModule(string $value = ''): void
    {
        if (empty($value)) {
            $this->loadTrainingModuleOptions();
            return;
        }

        $this->trainingModuleOptions = TrainingModule::with('competency')
            ->where('title', 'like', "%{$value}%")
            ->orderBy('title')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'group_comp' => $m->competency?->type ?? null
            ])
            ->toArray();
    }

    public function searchCompetency(string $value = ''): void
    {
        if (empty($value)) {
            $this->loadCompetencyOptions($this->parseCompetencyIdFromValue($this->competency_id));
            return;
        }

        $options = Competency::query()
            ->select('id', 'code', 'name')
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
            ->toArray();

        // Ensure current selection remains visible in the list (important for edit mode)
        $selectedId = $this->parseCompetencyIdFromValue($this->competency_id);
        if ($selectedId) {
            $existingIds = array_column($options, 'id');
            if (!in_array($selectedId, $existingIds, true)) {
                $selected = Competency::query()
                    ->select('id', 'code', 'name')
                    ->find($selectedId);
                if ($selected) {
                    array_unshift($options, [
                        'id' => $selected->id,
                        'name' => trim($selected->code . ' - ' . $selected->name),
                    ]);
                }
            }
        }

        $this->competencyOptions = $options;
    }

    // Fallback universal watcher to ensure course->group sync always runs
    public function updated($name, $value): void
    {
        if ($name === 'course_id') {
            $this->updatedCourseId($value);
        }

        // Fallback to ensure competency -> training name/group sync runs reliably for OUT-house
        if ($name === 'competency_id') {
            $this->updatedCompetencyId($value);
        }
    }


    public function saveTraining()
    {
        // Validation using FormRequest
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

        // Parse date range
        $range = $this->parseDateRange($this->date);
        $startDate = $range['start'];
        $endDate = $range['end'];

        // Validate time range
        if ($this->start_time && $this->end_time) {
            $startTime = Carbon::createFromFormat('H:i', $this->start_time);
            $endTime = Carbon::createFromFormat('H:i', $this->end_time);
            if ($endTime->lessThanOrEqualTo($startTime)) {
                $this->addError('end_time', 'End time must be later than start time.');
                $this->error('End time must be later than start time.', position: 'toast-top toast-center');
                return;
            }
        }

        // Sync course_id for LMS/BLENDED (form binds to selected_module_id)
        if (in_array($this->training_type, ['LMS', 'BLENDED'])) {
            $this->course_id = $this->selected_module_id;
        }

        // Prepare data for service
        $formData = [
            'training_name' => $this->training_name,
            'training_type' => $this->training_type,
            'course_id' => $this->course_id,
            'module_id' => $this->selected_module_id,
            'competency_id' => $this->competency_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'trainer_id' => $this->trainerId,
            'room' => $this->room,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];

        $persistService = app(TrainingPersistService::class);
        $sessionService = app(SessionSyncService::class);

        try {
            if ($this->isEdit && $this->trainingId) {
                // Update existing training
                $training = $persistService->update(
                    $this->trainingId,
                    $formData,
                    $this->participants,
                    $sessionService
                );
                
                if (!$training) {
                    $this->error('Training not found', position: 'toast-top toast-center');
                    return;
                }
                
                $this->success('Training updated successfully!', position: 'toast-top toast-center');
                $this->dispatch('training-updated', id: $training->id);
            } else {
                // Create new training
                $training = $persistService->create(
                    $formData,
                    $this->participants,
                    $sessionService
                );
                
                $this->success('Training data created successfully!', position: 'toast-top toast-center');
                $this->dispatch('training-created');
            }
            
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->error('Failed to save training: ' . $e->getMessage(), position: 'toast-top toast-center');
        }
    }

    /**
     * Ambil data validasi dari property Livewire
     */
    private function getValidationData(): array
    {
        return [
            'training_name' => $this->training_name,
            'training_type' => $this->training_type,
            'competency_id' => $this->competency_id,
            'date' => $this->date,
            'trainerId' => $this->trainerId,
            'course_id' => $this->course_id,
            'room' => $this->room,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'participants' => $this->participants,
        ];
    }

    // =====================================================================
    // NOTE: The following methods have been extracted to services:
    // - updateTraining, updateTrainingFields, updateSessionFields -> TrainingPersistService
    // - updateParticipantsAndSurveyResponses, updateAttendance -> SessionSyncService
    // - createSurveysForTraining, createSurveyResponsesForParticipants -> SessionSyncService
    // - resolveLevel3ApproverIds, resolveLevel3ApproverIdForParticipant -> SessionSyncService
    // - createSessionsForTraining, rebuildSessions -> SessionSyncService
    // =====================================================================

    public function render()
    {
        // PERFORMANCE: Only sync data when modal is visible
        // Cache ensures no duplicate queries even if sync runs
        if ($this->showModal) {
            // Defensive sync: ensure UI always reflects DB group for LMS selection
            if ($this->training_type === 'LMS' && !empty($this->course_id)) {
                $dbGroup = $this->getCourseGroupComp((int) $this->course_id);
                if ($dbGroup !== null) {
                    $trimmed = trim((string) $dbGroup);
                    if ($this->group_comp !== $trimmed) {
                        $this->group_comp = $trimmed;
                    }
                }
            }

            // Defensive sync: OUT-house competency should auto-fill group + name
            if ($this->training_type === 'OUT' && !empty($this->competency_id)) {
                $shouldAutofillName = trim((string) $this->training_name) === ''
                    || $this->trainingNameWasAutoFilled
                    || !$this->trainingNameManuallyEdited;

                $shouldSyncGroup = trim((string) $this->group_comp) === '' || $this->group_comp === 'BMC';

                if ($shouldAutofillName || $shouldSyncGroup) {
                    $id = $this->parseCompetencyIdFromValue($this->competency_id);

                    if ($id) {
                        $dbGroup = $this->getCompetencyGroupComp($id);
                        if ($dbGroup !== null && $this->group_comp !== $dbGroup) {
                            $this->group_comp = $dbGroup;
                        }
                    }

                    if ($shouldAutofillName) {
                        $name = $id ? $this->getCompetencyTrainingName($id) : null;
                        if ($name === null) {
                            $name = $this->parseCompetencyNameFromValue($this->competency_id);
                        }
                        if ($name !== null && $this->training_name !== $name) {
                            $this->setTrainingNameProgrammatically($name, autoFilled: true);
                        }
                    }
                }
            }
        }
        
        return view('components.training-schedule.training-form-modal');
    }
}
