<?php

namespace App\Services;

use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplateDefault;
use App\Models\Training;
use App\Models\TrainingSurvey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainingSurveyService
{
    /**
     * Create all surveys (Level 1 and Level 3) when training is closed.
     * - Level 1: Filled by training participants
     * - Level 3: Filled by supervisors of participants
     * 
     * Note: Level 2 does not exist in this system.
     */
    public function createSurveysOnClose(Training $training): array
    {
        return DB::transaction(function () use ($training) {
            $surveys = [];
            
            // Get participants from training assessments
            $participantIds = $training->assessments()
                ->pluck('employee_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            
            // Create Level 1 survey (for participants)
            $surveys[1] = $this->createSurveyWithResponses(
                $training, 
                1, 
                $participantIds
            );
            
            // Create Level 3 survey (for supervisors of participants)
            $approverIds = $this->resolveLevel3ApproverIds($participantIds);
            $surveys[3] = $this->createSurveyWithResponses(
                $training, 
                3, 
                $approverIds
            );
            
            return $surveys;
        });
    }

    /**
     * Create a survey with questions from template and responses for given employee IDs.
     */
    private function createSurveyWithResponses(Training $training, int $level, array $employeeIds): ?TrainingSurvey
    {
        // Get default template for this level
        $defaultTemplate = SurveyTemplateDefault::getDefaultTemplate($level);
        
        // Check if survey already exists
        $existingSurvey = TrainingSurvey::where('training_id', $training->id)
            ->where('level', $level)
            ->first();

        if ($existingSurvey) {
            // If survey exists but is draft with no questions, populate from template
            if (
                $existingSurvey->status === TrainingSurvey::STATUS_DRAFT
                && $existingSurvey->questions()->count() === 0
                && $defaultTemplate
            ) {
                $this->copyQuestionsFromTemplate($existingSurvey, $defaultTemplate);
                
                // Update status if questions were added
                $questionCount = $existingSurvey->questions()->count();
                if ($questionCount >= 3) {
                    $existingSurvey->status = TrainingSurvey::STATUS_INCOMPLETE;
                    $existingSurvey->save();
                }
            }
            
            // Create responses for employees who don't have one yet
            $this->createResponsesForEmployees($existingSurvey, $employeeIds);
            
            return $existingSurvey;
        }

        // Create new survey
        $hasEnoughQuestions = $defaultTemplate && $defaultTemplate->questions()->count() >= 3;
        
        $survey = TrainingSurvey::create([
            'training_id' => $training->id,
            'level' => $level,
            'status' => $hasEnoughQuestions ? TrainingSurvey::STATUS_INCOMPLETE : TrainingSurvey::STATUS_DRAFT,
        ]);

        // Copy questions from template
        if ($defaultTemplate) {
            $this->copyQuestionsFromTemplate($survey, $defaultTemplate);
        }
        
        // Create responses for all employees
        $this->createResponsesForEmployees($survey, $employeeIds);

        return $survey;
    }

    /**
     * Create survey responses for given employee IDs.
     */
    private function createResponsesForEmployees(TrainingSurvey $survey, array $employeeIds): void
    {
        foreach ($employeeIds as $employeeId) {
            SurveyResponse::firstOrCreate([
                'survey_id' => $survey->id,
                'employee_id' => (int) $employeeId,
            ]);
        }
    }

    /**
     * Resolve Level 3 approver IDs for a list of participant IDs.
     * Approver hierarchy:
     * - Employee -> SPV -> Section Head -> Dept Head
     * - Supervisor -> Section Head -> Dept Head
     * - Section Head -> Dept Head
     */
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
            $approverId = $this->resolveApproverForParticipant($participant);
            if ($approverId) {
                $approverIds[] = $approverId;
            }
        }

        return array_values(array_unique(array_filter($approverIds)));
    }

    /**
     * Find the approver for a specific participant based on their position.
     */
    private function resolveApproverForParticipant(User $participant): ?int
    {
        $position = strtolower(trim($participant->position ?? ''));
        $section = (string) ($participant->section ?? '');
        $department = (string) ($participant->department ?? '');

        // Supervisor -> find Section Head or Dept Head
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

        // Section Head -> find Dept Head
        if ($position === 'section_head') {
            $deptHead = User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['department_head'])
                ->when($department !== '', fn($q) => $q->where('department', $department))
                ->whereRaw('LOWER(TRIM(COALESCE(section, ""))) != ?', ['lid'])
                ->first();

            return $deptHead ? (int) $deptHead->id : null;
        }

        // Default (Employee) -> find SPV -> Section Head -> Dept Head
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
     * Copy questions from template to survey.
     */
    protected function copyQuestionsFromTemplate(TrainingSurvey $survey, $template): void
    {
        $templateQuestions = $template->questions()
            ->with('options')
            ->orderBy('order')
            ->get();

        foreach ($templateQuestions as $templateQuestion) {
            $question = SurveyQuestion::create([
                'training_survey_id' => $survey->id,
                'text' => $templateQuestion->text,
                'question_type' => $templateQuestion->question_type,
                'order' => $templateQuestion->order,
            ]);

            foreach ($templateQuestion->options as $templateOption) {
                SurveyOption::create([
                    'question_id' => $question->id,
                    'text' => $templateOption->text,
                    'order' => $templateOption->order ?? 0,
                ]);
            }
        }
    }

    /**
     * Legacy method - kept for backward compatibility.
     * @deprecated Use createSurveysOnClose() instead
     */
    public function createSurveyForTraining(Training $training): ?TrainingSurvey
    {
        $surveys = $this->createSurveysOnClose($training);
        return $surveys[1] ?? null;
    }
}
