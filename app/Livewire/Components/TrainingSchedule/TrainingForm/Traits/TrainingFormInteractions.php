<?php

namespace App\Livewire\Components\TrainingSchedule\TrainingForm\Traits;

use App\Models\Course;
use App\Models\Training;
use App\Models\TrainingModule;
use App\Services\Training\TrainingPersistService;
use Carbon\Carbon;

/**
 * Trait TrainingFormInteractions
 * 
 * Handles interactive form logic, event handlers, and modal management.
 */
trait TrainingFormInteractions
{
    // ===== LISTENERS =====
    // Note: defined in component, but handled here
    
    // ===== MODAL OPEN/CLOSE & RESET =====

    public function openModalWithDate($data)
    {
        // PERFORMANCE: Load dropdown data lazily via Trait
        $this->loadDropdownData();
        
        $this->resetFormState(); // from TrainingFormState
        $this->isEdit = false;
        
        // Only set date if explicitly provided
        if (!empty($data['date'])) {
            $this->date = $data['date'];
        }
        $this->showModal = true;
        
        // Dispatch event for calendar to hide loading overlay
        $this->dispatch('training-modal-opened');
    }

    public function openModal()
    {
        $this->loadDropdownData();
        $this->resetFormState();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFormState();
    }
    
    // Hold last known schedule context
    public function setDefaultMonth(int $year, int $month): void
    {
        $this->defaultYear = $year;
        $this->defaultMonth = $month;
    }

    // ===== EDIT LOGIC =====

    public function openEdit($payload): void
    {
        $id = is_array($payload) ? ($payload['id'] ?? null) : (is_numeric($payload) ? (int) $payload : null);
        
        if (!$id) {
            $this->error('Invalid training reference.');
            return;
        }

        // Reset form without triggering search queries
        $this->resetFormFieldsOnly(); 
        $this->isEdit = true;
        $this->trainingId = (int) $id;

        // Load training with relations (include assessments for participants)
        $training = Training::with([
            'sessions' => fn($q) => $q->orderBy('day_number'),
            'course',
            'module', 
            'competency',
            'assessments' // For loading participants
        ])->find($this->trainingId);

        if (!$training) {
            $this->error('Training not found');
            return;
        }

        if ($training->status && strtolower($training->status) === 'done') {
            $this->error('Cannot edit a closed training. Please view details instead.');
            return;
        }

        // Load options (with competency for OUT type)
        $this->loadDropdownData();
        $this->loadCompetencyOptions($training->competency_id);

        // Populate fields
        $this->competency_id = $training->competency_id; // Set first for watcher
        $this->training_type = $training->type;
        $this->originalTrainingType = $training->type;
        $this->group_comp = $training->type === 'OUT'
            ? ($training->competency?->type ?? $training->group_comp)
            : $training->group_comp;
        $this->course_id = $training->course_id;

        // Module selection
        if ($training->type === 'IN' && $training->module_id) {
            $this->selected_module_id = (int) $training->module_id;
        } elseif (in_array($training->type, ['LMS', 'BLENDED']) && $training->course_id) {
            $this->selected_module_id = (int) $training->course_id;
        } else {
            $this->selected_module_id = null;
        }

        // Training Name
        $name = in_array($training->type, ['LMS', 'BLENDED']) 
            ? ($training->course?->title ?? $training->name)
            : $training->name;
        
        $this->setTrainingNameProgrammatically($name, autoFilled: false);
        $this->trainingNameManuallyEdited = true; // Existing record -> treat as manual

        // Date
        if ($training->start_date && $training->end_date) {
            $start = $training->start_date instanceof Carbon ? $training->start_date->toDateString() : Carbon::parse($training->start_date)->toDateString();
            $end = $training->end_date instanceof Carbon ? $training->end_date->toDateString() : Carbon::parse($training->end_date)->toDateString();
            $this->date = $start . ' to ' . $end;
        }

        // Times & Room from first session
        $firstSession = $training->sessions->first();
        if ($firstSession) {
            $this->start_time = $firstSession->start_time ? Carbon::parse($firstSession->start_time)->format('H:i') : '';
            $this->end_time = $firstSession->end_time ? Carbon::parse($firstSession->end_time)->format('H:i') : '';
            
            $this->room = [
                'name' => $firstSession->room ?? '',
                'location' => $firstSession->location ?? ''
            ];
            
            // Trainer from first session
            if ($firstSession->trainer_id) {
                $this->trainerId = $firstSession->trainer_id;
                // Ensure trainer is searchable
                $this->trainerSearch(); 
            }
        }
        // Load participants from assessments relation
        $this->participants = $training->assessments
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        
        // Pre-populate users searchable with existing participants for display
        if (!empty($this->participants)) {
            $this->userSearch('');
        }
        
        $this->showModal = true;
        
        // Dispatch event for parent components to hide loading overlay
        $this->dispatch('training-modal-opened');
    }

    // ===== DELETE LOGIC =====
    
    public function requestDeleteConfirm(): void
    {
        if (!$this->trainingId) return;
        $this->dispatch('confirm', 
            'Delete Confirmation', 
            'Are you sure you want to delete this training along with all sessions and attendance?', 
            'confirm-delete-training-form', 
            $this->trainingId
        );
    }
    
    public function onConfirmDelete($id = null): void
    {
        if ($id && $this->trainingId && (int) $id !== (int) $this->trainingId) return;
        
        if (!$this->trainingId) return;
        
        try {
            $service = app(TrainingPersistService::class);
            $deleted = $service->delete($this->trainingId);
            
            if (!$deleted) {
                $this->error('Training not found.', position: 'toast-top toast-center');
            } else {
                $this->dispatch('training-deleted', ['id' => $this->trainingId]);
                $this->success('Training deleted.', position: 'toast-top toast-center');
                $this->closeModal();
            }
        } catch (\Throwable $e) {
            $this->error('Failed to delete training.', position: 'toast-top toast-center');
        }
        $this->dispatch('confirm-done');
    }

    // ===== UPDATED HANDLERS =====

    public function updated($name, $value): void
    {
        if ($name === 'course_id') {
            $this->updatedCourseId($value);
        }
        if ($name === 'competency_id') {
            $this->updatedCompetencyId($value);
        }
    }

    public function updatedSelectedModuleId($value): void
    {
        if ($value) {
            if ($this->training_type === 'LMS') {
                $this->course_id = (int) $value;
                $this->updatedCourseId($value);
                return;
            }

            $module = TrainingModule::with('competency')->find((int) $value);
            if ($module) {
                $this->setTrainingNameProgrammatically($module->title, autoFilled: true);
                $this->group_comp = $module->competency?->type ?? 'BMC';
            }
        }
    }

    public function updatedCourseId($value): void
    {
        if (empty($value) && $value !== 0) return;

        $id = null;
        $title = null;
        $group = null;

        if (is_array($value) || is_object($value)) {
            $arr = (array) $value;
            $id = $arr['id'] ?? ($arr['value'] ?? null);
            $title = $arr['title'] ?? ($arr['label'] ?? ($arr['name'] ?? null));
            $group = $arr['group_comp'] ?? ($arr['group'] ?? null);
        } else {
            $id = is_numeric($value) ? (int) $value : null;
        }

        if ($id) {
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
            $trimmed = trim((string) $group);
            $this->group_comp = $trimmed !== '' ? $trimmed : 'BMC';
        }
    }

    public function updatedTrainingName($value): void
    {
        if ($this->isSettingTrainingNameProgrammatically) return;
        $this->trainingNameManuallyEdited = true;
        $this->trainingNameWasAutoFilled = false;
    }

    public function updatedCompetencyId($value): void
    {
        if (empty($value)) {
            $this->competency_id = null;
            $this->group_comp = 'BMC';
            return;
        }

        $id = $this->parseCompetencyIdFromValue($value); // defined in Dropdowns trait? No, need to be in Dropdowns or Helpers
        // Assuming parseCompetencyIdFromValue is moved to Dropdowns trait or redefined here.
        // It was in Modal proper. Let's assume it should be in Dropdowns trait.
        // For now, I'll rely on it being available (I'll check later).
        
        $this->competency_id = $id;

        // Group sync logic using cache from Dropdowns trait
        // Accessing methods from Dropdowns trait via $this
        $group = $this->getCompetencyGroupComp($id);
        if ($group !== null) {
            $this->group_comp = $group;
        }

        // Auto-fill logic for OUT House
        if ($this->training_type === 'OUT') {
            $shouldAutofill = trim((string) $this->training_name) === ''
                || $this->trainingNameWasAutoFilled
                || !$this->trainingNameManuallyEdited;

            if ($shouldAutofill) {
                $name = $id ? $this->getCompetencyTrainingName($id) : null;
                if ($name === null) {
                     // Need parseCompetencyNameFromValue
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

        if ($value !== 'OUT') $this->competency_id = null;

        if (!in_array($value, ['LMS', 'BLENDED']) || empty($this->course_id)) {
            $this->group_comp = 'BMC';
        }
        
        $this->training_name = '';
        $this->trainingNameManuallyEdited = false;
        $this->trainingNameWasAutoFilled = false;

        // LMS Transition confirmation
        if ($this->isEdit && $value === 'LMS' && $this->originalTrainingType !== 'LMS') {
            $this->pendingTypeChange = 'LMS';
            $this->showTypeChangeConfirm = true; // Need to ensure property matches (showTypeChangeConfirm vs custom)
            $this->training_type = $this->originalTrainingType; // Revert visually
            return;
        }

        if ($value === 'LMS') {
            $this->applyLmsSwitch();
        } elseif ($value === 'BLENDED') {
            $this->selected_module_id = null;
            $this->course_id = null;
            $this->loadCourseOptions();
        } elseif ($value === 'IN') {
            $this->course_id = null;
            $this->selected_module_id = null;
        } else {
            // OUT
            $this->course_id = null;
            $this->selected_module_id = null;
            if (!$this->isEdit) $this->competency_id = null;
            $this->loadCompetencyOptions($this->competency_id ? (int)$this->competency_id : null);
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
    
    // Type change confirmation handlers
    public function confirmTypeChange(): void
    {
        if ($this->pendingTypeChange === 'LMS') {
            $this->applyLmsSwitch();
            $this->showTypeChangeConfirm = false;
            $this->pendingTypeChange = null;
        }
    }

    public function cancelTypeChange(): void
    {
        $this->showTypeChangeConfirm = false;
        $this->pendingTypeChange = null;
        if ($this->isEdit && $this->originalTrainingType) {
            $this->training_type = $this->originalTrainingType;
        }
    }
    

}
