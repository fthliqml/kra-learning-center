<?php


namespace App\Livewire\Components\Training;

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

    private function loadCourseOptions(): void
    {
        // Include group_comp so we can auto-sync training group_comp for LMS
        $this->courseOptions = Course::with('competency:id,type')
            ->select('id', 'title', 'competency_id')
            ->orderBy('title')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'group_comp' => $c->competency->type ?? null])->toArray();
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
        $options = Competency::query()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => trim($c->code . ' - ' . $c->name)])
            ->toArray();

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
        $this->trainingModuleOptions = TrainingModule::with('competency')
            ->orderBy('title')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'group_comp' => $m->competency?->type ?? null
            ])->toArray();
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
                TrainingSession::where('training_id', $id)->delete();
                // Delete assessments
                TrainingAssessment::where('training_id', $id)->delete();
                // Finally delete training
                $training->delete();
            });

            // Notify parent and close
            $this->dispatch('training-deleted', ['id' => $id]);
            $this->success('Training deleted.', position: 'toast-top toast-center');
            $this->showModal = false;
            $this->resetForm();
            // Close confirm dialog and stop spinner
            $this->dispatch('confirm-done');
        } catch (\Throwable $e) {
            $this->error('Failed to delete training.');
            $this->dispatch('confirm-done');
        }
    }

    public function openEdit($payload): void
    {
        // PERFORMANCE: Load dropdown data lazily
        $this->loadDropdownData();
        
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

        // Reload options FIRST to ensure selected module/course/competency is included
        $this->loadCourseOptions();
        $this->loadTrainingModuleOptions();
        $this->loadCompetencyOptions($training->competency_id);

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

        // Refresh searchable lists so that selected participants/trainers appear
        $this->userSearch();
        $this->trainerSearch();

        $this->activeTab = 'training';
        $this->showModal = true;

        // Force component to refresh after data is loaded
        $this->dispatch('training-edit-loaded');
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

        // If no search value, load all trainers
        if (empty($value)) {
            $results = Trainer::with('user')->get();
            $this->trainersSearchable = $results->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name ?: ($trainer->user?->name ?? 'Unknown'),
                ];
            });
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
        if (in_array($this->training_type, ['LMS', 'BLENDED'])) {
            // Form binds course selection to selected_module_id for LMS/BLENDED
            // Sync to course_id for consistency
            $this->course_id = $this->selected_module_id;
            $course = Course::find($this->course_id);
            $courseTitle = $course?->title;
        }

        if ($this->isEdit && $this->trainingId) {
            $this->updateTraining($courseTitle, $startDate, $endDate);
            return;
        }

        // group_comp is no longer persisted on trainings; keep it as UI-only display value.
        if (in_array($this->training_type, ['LMS', 'BLENDED']) && $this->course_id) {
            $syncedGroup = $this->getCourseGroupComp((int) $this->course_id);
            if ($syncedGroup) {
                $this->group_comp = $syncedGroup;
            }
        }

        if ($this->training_type === 'OUT' && $this->competency_id) {
            $syncedGroup = $this->getCompetencyGroupComp((int) $this->competency_id);
            if ($syncedGroup) {
                $this->group_comp = $syncedGroup;
            }
        }

        $finalName = $this->training_name;
        if ($this->training_type === 'OUT' && trim((string) $finalName) === '' && $this->competency_id) {
            $fallback = $this->getCompetencyTrainingName((int) $this->competency_id);
            if ($fallback !== null) {
                $finalName = $fallback;
            }
        }

        $training = Training::create([
            'name' => in_array($this->training_type, ['LMS', 'BLENDED']) ? ($courseTitle ?? $this->training_type) : $finalName,
            'type' => $this->training_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'course_id' => in_array($this->training_type, ['LMS', 'BLENDED']) ? $this->course_id : null,
            'module_id' => $this->training_type === 'IN' ? $this->selected_module_id : null,
            'competency_id' => $this->training_type === 'OUT' ? $this->competency_id : null,
        ]);

        // LMS trainings don't have surveys - skip survey creation for LMS type
        if ($this->training_type !== 'LMS') {
            $surveys = $this->createSurveysForTraining($training);
            $this->createSurveyResponsesForParticipants($surveys, $this->participants);
        }

        $sessions = $this->createSessionsForTraining($training, $startDate, $endDate);

        foreach ($this->participants as $participantId) {
            TrainingAssessment::create(["training_id" => $training->id, "employee_id" => $participantId]);
        }

        if ($this->training_type !== 'LMS') {
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
        if (in_array($this->training_type, ['LMS', 'BLENDED'])) {
            // LMS and BLENDED use course
            $training->course_id = $this->course_id;
            $training->module_id = null;
            $training->competency_id = null;
            $training->name = $courseTitle ?? $training->name;
        } elseif ($this->training_type === 'IN') {
            $training->course_id = null;
            $training->module_id = $this->selected_module_id;
            $training->competency_id = null;
            $training->name = $this->training_name;
        } else {
            // OUT type
            $training->course_id = null;
            $training->module_id = null;
            $training->competency_id = $this->competency_id;
            $name = $this->training_name;
            if (trim((string) $name) === '' && $this->competency_id) {
                $fallback = $this->getCompetencyTrainingName((int) $this->competency_id);
                if ($fallback !== null) {
                    $name = $fallback;
                }
            }
            $training->name = $name;
        }

        // group_comp is no longer persisted on trainings; keep it as UI-only display value.
        if (in_array($this->training_type, ['LMS', 'BLENDED']) && $this->course_id) {
            $syncedGroup = $this->getCourseGroupComp((int) $this->course_id);
            if ($syncedGroup) {
                $this->group_comp = $syncedGroup;
            }
        } elseif ($this->training_type === 'OUT' && $this->competency_id) {
            $syncedGroup = $this->getCompetencyGroupComp((int) $this->competency_id);
            if ($syncedGroup) {
                $this->group_comp = $syncedGroup;
            }
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
            if ($training->type === 'LMS') {
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

        // Level 1 & 2: responses belong to participants.
        foreach ($toAdd as $empId) {
            foreach ($surveys as $survey) {
                if ((int) ($survey->level ?? 0) === 3) {
                    continue;
                }
                SurveyResponse::firstOrCreate([
                    'survey_id' => $survey->id,
                    'employee_id' => $empId,
                ]);
            }
        }

        if (!empty($toRemove)) {
            foreach ($surveys as $survey) {
                if ((int) ($survey->level ?? 0) === 3) {
                    continue;
                }
                SurveyResponse::where('survey_id', $survey->id)
                    ->whereIn('employee_id', $toRemove)
                    ->delete();
            }
        }

        // Level 3: responses belong to the expected approvers of current participants.
        $level3Survey = $surveys->firstWhere('level', 3);
        if ($level3Survey) {
            $desiredApproverIds = $this->resolveLevel3ApproverIds($newParticipantIds);

            $existingResponderIds = SurveyResponse::where('survey_id', $level3Survey->id)
                ->pluck('employee_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            $toAddApprovers = array_diff($desiredApproverIds, $existingResponderIds);
            $toRemoveApprovers = array_diff($existingResponderIds, $desiredApproverIds);

            foreach ($toAddApprovers as $approverId) {
                SurveyResponse::firstOrCreate([
                    'survey_id' => $level3Survey->id,
                    'employee_id' => (int) $approverId,
                ]);
            }

            if (!empty($toRemoveApprovers)) {
                SurveyResponse::where('survey_id', $level3Survey->id)
                    ->whereIn('employee_id', $toRemoveApprovers)
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
        if ($training->type !== 'LMS') {
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

        // Level 1 & 2: responses belong to training participants.
        foreach ($participants as $participantId) {
            foreach ($surveys as $survey) {
                if ((int) ($survey->level ?? 0) === 3) {
                    continue;
                }
                SurveyResponse::firstOrCreate([
                    'survey_id' => $survey->id,
                    'employee_id' => $participantId,
                ]);
            }
        }

        // Level 3: responses belong to the participant's approver (SPV/Section Head/Dept Head).
        $level3Survey = $surveys[3] ?? null;
        if ($level3Survey && (int) ($level3Survey->level ?? 0) === 3) {
            $approverIds = $this->resolveLevel3ApproverIds(array_map('intval', $participants));
            foreach ($approverIds as $approverId) {
                SurveyResponse::firstOrCreate([
                    'survey_id' => $level3Survey->id,
                    'employee_id' => $approverId,
                ]);
            }
        }
    }

    private function resolveLevel3ApproverIds(array $participantIds): array
    {
        if (empty($participantIds)) {
            return [];
        }

        $participants = User::query()
            ->whereIn('id', $participantIds)
            ->get(['id', 'section', 'department', 'position']);

        $approverIds = [];
        foreach ($participants as $participant) {
            $approverId = $this->resolveLevel3ApproverIdForParticipant($participant);
            if ($approverId) {
                $approverIds[] = $approverId;
            }
        }

        return array_values(array_unique(array_filter($approverIds)));
    }

    /**
     * Level 3 approver priority by participant area:
     * - employee submits: SPV -> Section Head -> Dept Head
     * - supervisor submits: Section Head -> Dept Head
     * - section head submits: Dept Head
     */
    private function resolveLevel3ApproverIdForParticipant(User $participant): ?int
    {
        $position = strtolower(trim($participant->position ?? ''));
        $section = (string) ($participant->section ?? '');
        $department = (string) ($participant->department ?? '');

        if ($position === 'supervisor') {
            $sectionHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
                ->when($section !== '', fn($q) => $q->where('section', $section))
                ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
                ->first();
            if ($sectionHead) {
                return (int) $sectionHead->id;
            }

            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();

            return $deptHead ? (int) $deptHead->id : null;
        }

        if ($position === 'section_head') {
            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();

            return $deptHead ? (int) $deptHead->id : null;
        }

        $spv = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
            ->when($section !== '', fn($q) => $q->where('section', $section))
            ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
            ->first();
        if ($spv) {
            return (int) $spv->id;
        }

        $sectionHead = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['section_head'])
            ->when($section !== '', fn($q) => $q->where('section', $section))
            ->when($section === '' && $department !== '', fn($q) => $q->where('department', $department))
            ->first();
        if ($sectionHead) {
            return (int) $sectionHead->id;
        }

        $deptHead = User::query()
            ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
            ->when($department !== '', fn($q) => $q->where('department', $department))
            ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
            ->first();

        return $deptHead ? (int) $deptHead->id : null;
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
                    'trainer_id' => $this->training_type === 'LMS' ? null : $this->trainerId,
                    'room_name' => $this->training_type === 'LMS' ? ($this->room['name'] ?: null) : $this->room['name'],
                    'room_location' => $this->training_type === 'LMS' ? ($this->room['location'] ?: null) : $this->room['location'],
                    'start_time' => $this->training_type === 'LMS' ? null : $this->start_time,
                    'end_time' => $this->training_type === 'LMS' ? null : $this->end_time,
                ]);
                $day++;
            }
        }
        return $sessions;
    }

    /**
     * Rebuild sessions when date range or type changes. Removes old sessions and recreates sequential days.
     * Keeps semantics:
     *  - LMS: no trainer / times (null), optional room.
     *  - Non LMS: apply trainer/time/room values from form.
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
                'trainer_id' => $training->type === 'LMS' ? null : $this->trainerId,
                'room_name' => $training->type === 'LMS' ? ($this->room['name'] ?: null) : $this->room['name'],
                'room_location' => $training->type === 'LMS' ? ($this->room['location'] ?: null) : $this->room['location'],
                'start_time' => $training->type === 'LMS' ? null : $this->start_time,
                'end_time' => $training->type === 'LMS' ? null : $this->end_time,
            ]);
            $day++;
        }
        // Reload relation for subsequent logic
        $training->load('sessions');
    }

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
        
        return view('components.training.training-form-modal');
    }
}
