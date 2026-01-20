<?php

namespace App\Livewire\Components\TrainingSchedule;

use App\Http\Requests\TrainingFormRequest;
use App\Models\Course;
use App\Services\Training\SessionSyncService;
use App\Services\Training\TrainingPersistService;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Livewire\Components\TrainingSchedule\TrainingForm\Traits\TrainingFormState;
use App\Livewire\Components\TrainingSchedule\TrainingForm\Traits\TrainingFormDropdowns;
use App\Livewire\Components\TrainingSchedule\TrainingForm\Traits\TrainingFormInteractions;

class TrainingFormModal extends Component
{
    use Toast;
    use TrainingFormState;
    use TrainingFormDropdowns;
    use TrainingFormInteractions;

    public $activeTab = 'training'; // training or session

    protected $listeners = [
        'open-add-training-modal' => 'openModalWithDate',
        'schedule-month-context' => 'setDefaultMonth',
        'open-training-form-edit' => 'openEdit',
        'confirm-delete-training-form' => 'onConfirmDelete'
    ];

    public function mount()
    {
        // PERFORMANCE: Don't load anything on mount!
        // Data will be loaded lazily via loadDropdownData() from trait when modal opens
        $this->initSearchables(); // Initialize empty collections from Dropdowns Trait
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

    public function render()
    {
        // PERFORMANCE: Only sync data when modal is visible
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

                    // Note: Name autofill logic is handled in updatedCompetencyId via Interactions trait
                    // But we keep this as fallback render-time sync if needed
                    // For now, relies on Traits logic.
                }
            }
        }
        
        return view('components.training-schedule.training-form-modal');
    }
}
