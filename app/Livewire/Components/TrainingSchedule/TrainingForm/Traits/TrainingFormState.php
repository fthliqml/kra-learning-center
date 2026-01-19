<?php

namespace App\Livewire\Components\TrainingSchedule\TrainingForm\Traits;

/**
 * Trait TrainingFormState
 * 
 * Shared state properties and reset methods for Training Form.
 * Used by TrainingFormModal as the main state holder.
 */
trait TrainingFormState
{
    // ===== MODAL STATE =====
    public bool $showModal = false;
    public bool $loading = true;
    
    // ===== MODE & IDENTITY =====
    public bool $isEdit = false;
    public ?int $trainingId = null;
    
    // ===== FORM FIELDS =====
    public string $training_name = '';
    public string $training_type = 'IN';
    public string $group_comp = 'BMC';
    public ?int $selected_module_id = null;
    public ?int $course_id = null;
    public $competency_id = null;
    public string $date = '';
    public string $start_time = '';
    public string $end_time = '';
    public ?int $trainerId = null;
    public array $room = ['name' => '', 'location' => ''];
    public array $participants = [];
    
    // ===== ORIGINAL TYPE (for edit mode type change detection) =====
    public ?string $originalTrainingType = null;
    
    // ===== UI FLAGS =====
    public bool $trainingNameManuallyEdited = false;
    public bool $trainingNameWasAutoFilled = false;
    protected bool $isSettingTrainingNameProgrammatically = false;
    
    // Pending type change for confirmation modal
    public ?string $pendingTypeChange = null;
    public bool $showTypeChangeConfirm = false;
    
    // Default month context from calendar
    public int $defaultYear;
    public int $defaultMonth;
    
    // ===== STATIC OPTIONS =====
    public array $trainingTypeOptions = [
        ['id' => 'IN', 'name' => 'In-House'],
        ['id' => 'OUT', 'name' => 'Out-House'],
        ['id' => 'LMS', 'name' => 'LMS'],
        ['id' => 'BLENDED', 'name' => 'Blended'],
    ];
    
    public array $groupCompOptions = [
        ['id' => 'BMC', 'name' => 'Basic Mandatory Competencies'],
        ['id' => 'FC', 'name' => 'Functional Competencies'],
        ['id' => 'LC', 'name' => 'Leadership Competencies'],
    ];

    /**
     * Reset all form fields to defaults.
     */
    public function resetFormState(): void
    {
        $this->isEdit = false;
        $this->trainingId = null;
        $this->training_name = '';
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->selected_module_id = null;
        $this->course_id = null;
        $this->competency_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->trainerId = null;
        $this->room = ['name' => '', 'location' => ''];
        $this->participants = [];
        $this->originalTrainingType = null;
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;
        $this->pendingTypeChange = null;
        $this->showTypeChangeConfirm = false;
    }

    /**
     * Reset form fields only without touching searchable data.
     * Used by openEdit to avoid duplicate queries.
     */
    public function resetFormFieldsOnly(): void
    {
        $this->isEdit = false;
        $this->trainingId = null;
        $this->training_name = '';
        $this->training_type = 'IN';
        $this->group_comp = 'BMC';
        $this->selected_module_id = null;
        $this->course_id = null;
        $this->competency_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->trainerId = null;
        $this->room = ['name' => '', 'location' => ''];
        $this->participants = [];
        $this->originalTrainingType = null;
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;
        $this->pendingTypeChange = null;
        $this->showTypeChangeConfirm = false;
        
        $this->resetErrorBag();
        $this->resetValidation();
    }
    
    /**
     * Get form data as array for service calls.
     */
    public function getFormDataForService(): array
    {
        return [
            'training_name' => $this->training_name,
            'training_type' => $this->training_type,
            'group_comp' => $this->group_comp,
            'module_id' => $this->selected_module_id,
            'course_id' => in_array($this->training_type, ['LMS', 'BLENDED']) 
                ? $this->selected_module_id  // Form binds course to selected_module_id for LMS/BLENDED
                : $this->course_id,
            'competency_id' => $this->competency_id,
            'start_date' => $this->parseDateRange($this->date)['start'] ?? null,
            'end_date' => $this->parseDateRange($this->date)['end'] ?? null,
            'trainer_id' => $this->trainerId,
            'room' => $this->room,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }

    /**
     * Parse date range string "YYYY-MM-DD to YYYY-MM-DD" into start/end.
     */
    protected function parseDateRange($dateRange): array
    {
        $dates = explode(' to ', (string) $dateRange);
        return [
            'start' => trim($dates[0] ?? ''),
            'end' => trim($dates[1] ?? $dates[0] ?? ''),
        ];
    }
    
    /**
     * Set training name programmatically (prevents triggering manual edit flag).
     */
    protected function setTrainingNameProgrammatically(?string $name, bool $autoFilled = false): void
    {
        $this->isSettingTrainingNameProgrammatically = true;
        $this->training_name = (string) ($name ?? '');
        $this->isSettingTrainingNameProgrammatically = false;

        $this->trainingNameWasAutoFilled = $autoFilled;
        if ($autoFilled) {
            $this->trainingNameManuallyEdited = false;
        }
    }
}
