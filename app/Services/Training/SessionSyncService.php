<?php

namespace App\Services\Training;

use App\Models\SurveyResponse;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use App\Models\TrainingSurvey;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Service for managing training sessions, attendances, and surveys.
 * Extracted from TrainingFormModal for cleaner separation of concerns.
 */
class SessionSyncService
{
    /**
     * Create sessions for a training between start and end date.
     *
     * @param Training $training
     * @param string $startDate
     * @param string $endDate
     * @param string $trainingType
     * @param int|null $trainerId
     * @param array $room ['name' => '', 'location' => '']
     * @param string|null $startTime
     * @param string|null $endTime
     * @param array $sessionOverrideConfig ['applyToAllDays' => bool, 'dayOverrides' => array]
     * @return array Array of created TrainingSession models
     */
    public function createSessionsForTraining(
        Training $training,
        string $startDate,
        string $endDate,
        string $trainingType,
        ?int $trainerId = null,
        array $room = ['name' => '', 'location' => ''],
        ?string $startTime = null,
        ?string $endTime = null,
        array $sessionOverrideConfig = []
    ): array {
        $sessions = [];
        
        if (!$startDate || !$endDate) {
            return $sessions;
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $day = 1;
        $isLms = $trainingType === 'LMS';
        
        $applyToAllDays = $sessionOverrideConfig['applyToAllDays'] ?? true;
        $dayOverrides = $sessionOverrideConfig['dayOverrides'] ?? [];

        foreach ($period as $dateObj) {
            // Start with global config
            $sessionRoom = $room;
            $sessionStartTime = $startTime;
            $sessionEndTime = $endTime;
            
            // Apply per-day override if exists
            if (!$applyToAllDays && isset($dayOverrides[$day])) {
                $override = $dayOverrides[$day];
                if (isset($override['room_name'])) {
                    $sessionRoom['name'] = $override['room_name'];
                }
                if (isset($override['room_location'])) {
                    $sessionRoom['location'] = $override['room_location'];
                }
                if (isset($override['start_time'])) {
                    $sessionStartTime = $override['start_time'];
                }
                if (isset($override['end_time'])) {
                    $sessionEndTime = $override['end_time'];
                }
            }
            
            $sessions[] = TrainingSession::create([
                'training_id' => $training->id,
                'day_number' => $day,
                'date' => $dateObj->format('Y-m-d'),
                'trainer_id' => $isLms ? null : $trainerId,
                'room_name' => $isLms ? ($sessionRoom['name'] ?: null) : $sessionRoom['name'],
                'room_location' => $isLms ? ($sessionRoom['location'] ?: null) : $sessionRoom['location'],
                'start_time' => $isLms ? null : $sessionStartTime,
                'end_time' => $isLms ? null : $sessionEndTime,
            ]);
            $day++;
        }

        return $sessions;
    }

    /**
     * Rebuild sessions when date range or type changes.
     * Removes old sessions and recreates sequential days.
     */
    public function rebuildSessions(
        Training $training,
        ?string $startDate,
        ?string $endDate,
        string $previousType,
        string $newType,
        ?int $trainerId = null,
        array $room = ['name' => '', 'location' => ''],
        ?string $startTime = null,
        ?string $endTime = null,
        array $sessionOverrideConfig = []
    ): void {
        // Delete all old sessions
        $training->sessions()->delete();

        if (!$startDate || !$endDate) {
            return;
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $day = 1;
        $isLms = $newType === 'LMS';
        
        $applyToAllDays = $sessionOverrideConfig['applyToAllDays'] ?? true;
        $dayOverrides = $sessionOverrideConfig['dayOverrides'] ?? [];

        foreach ($period as $dateObj) {
            // Start with global config
            $sessionRoom = $room;
            $sessionStartTime = $startTime;
            $sessionEndTime = $endTime;
            
            // Apply per-day override if exists
            if (!$applyToAllDays && isset($dayOverrides[$day])) {
                $override = $dayOverrides[$day];
                if (isset($override['room_name'])) {
                    $sessionRoom['name'] = $override['room_name'];
                }
                if (isset($override['room_location'])) {
                    $sessionRoom['location'] = $override['room_location'];
                }
                if (isset($override['start_time'])) {
                    $sessionStartTime = $override['start_time'];
                }
                if (isset($override['end_time'])) {
                    $sessionEndTime = $override['end_time'];
                }
            }
            
            TrainingSession::create([
                'training_id' => $training->id,
                'day_number' => $day,
                'date' => $dateObj->format('Y-m-d'),
                'trainer_id' => $isLms ? null : $trainerId,
                'room_name' => $isLms ? ($sessionRoom['name'] ?: null) : $sessionRoom['name'],
                'room_location' => $isLms ? ($sessionRoom['location'] ?: null) : $sessionRoom['location'],
                'start_time' => $isLms ? null : $sessionStartTime,
                'end_time' => $isLms ? null : $sessionEndTime,
            ]);
            $day++;
        }

        // Reload relation for subsequent logic
        $training->load('sessions');
    }

    /**
     * Update session fields if not rebuilding sessions.
     */
    public function updateSessionFields(
        Training $training,
        string $trainingType,
        ?int $trainerId = null,
        array $room = ['name' => '', 'location' => ''],
        ?string $startTime = null,
        ?string $endTime = null
    ): void {
        $isLms = $trainingType === 'LMS';

        foreach ($training->sessions as $session) {
            if ($isLms) {
                $session->room_name = $room['name'] ?: null;
                $session->room_location = $room['location'] ?: null;
                $session->trainer_id = null;
                $session->start_time = null;
                $session->end_time = null;
            } else {
                $session->trainer_id = $trainerId;
                $session->room_name = $room['name'];
                $session->room_location = $room['location'];
                $session->start_time = $startTime;
                $session->end_time = $endTime;
            }
            $session->save();
        }
    }

    /**
     * Update participants (assessments) and survey responses for this training.
     */
    public function updateParticipantsAndSurveyResponses(
        Training $training,
        array $participants,
        string $trainingType
    ): void {
        $existingParticipantIds = $training->assessments()
            ->pluck('employee_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
        
        $newParticipantIds = array_map('intval', $participants);
        $toAdd = array_diff($newParticipantIds, $existingParticipantIds);
        $toRemove = array_diff($existingParticipantIds, $newParticipantIds);

        // Add new participants
        foreach ($toAdd as $empId) {
            TrainingAssessment::create([
                'training_id' => $training->id,
                'employee_id' => $empId,
            ]);
        }

        // Remove old participants
        if (!empty($toRemove)) {
            TrainingAssessment::where('training_id', $training->id)
                ->whereIn('employee_id', $toRemove)
                ->delete();
        }

        // Update SurveyResponse for all surveys of this training
        $surveys = TrainingSurvey::where('training_id', $training->id)->get();

        // Level 1: responses belong to participants (training attendees)
        $level1Survey = $surveys->firstWhere('level', 1);
        if ($level1Survey) {
            foreach ($toAdd as $empId) {
                SurveyResponse::firstOrCreate([
                    'survey_id' => $level1Survey->id,
                    'employee_id' => $empId,
                ]);
            }

            if (!empty($toRemove)) {
                SurveyResponse::where('survey_id', $level1Survey->id)
                    ->whereIn('employee_id', $toRemove)
                    ->delete();
            }
        }

        // Level 3: responses belong to the expected approvers of current participants
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
     * Update attendance for all sessions and participants.
     */
    public function updateAttendance(Training $training, array $participants, string $trainingType): void
    {
        $newParticipantIds = array_map('intval', $participants);
        $sessionIds = $training->sessions()->pluck('id');
        
        // Delete existing attendances
        TrainingAttendance::whereIn('session_id', $sessionIds)->delete();
        
        // Create new attendances (skip for LMS)
        if ($trainingType !== 'LMS') {
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
     * Create surveys for Level 1 (participant) and Level 3 (supervisor) for a training.
     * Note: Level 2 does not exist in this system.
     *
     * @param Training $training
     * @return array Array of TrainingSurvey models indexed by level
     */
    public function createSurveysForTraining(Training $training): array
    {
        $surveys = [];
        
        // Level 1: Filled by training participants
        $surveys[1] = TrainingSurvey::create([
            'training_id' => $training->id,
            'level' => 1,
            'status' => TrainingSurvey::STATUS_DRAFT,
        ]);
        
        // Level 3: Filled by supervisors/approvers of participants
        $surveys[3] = TrainingSurvey::create([
            'training_id' => $training->id,
            'level' => 3,
            'status' => TrainingSurvey::STATUS_DRAFT,
        ]);
        
        return $surveys;
    }

    /**
     * Create survey responses for participants and their supervisors.
     * - Level 1: Responses created for each participant
     * - Level 3: Responses created for supervisors of participants
     */
    public function createSurveyResponsesForParticipants(array $surveys, array $participants): void
    {
        if (empty($participants) || empty($surveys)) {
            return;
        }

        // Level 1: Responses belong to training participants
        $level1Survey = $surveys[1] ?? null;
        if ($level1Survey) {
            foreach ($participants as $participantId) {
                SurveyResponse::firstOrCreate([
                    'survey_id' => $level1Survey->id,
                    'employee_id' => $participantId,
                ]);
            }
        }

        // Level 3: Responses belong to the participant's supervisor (SPV/Section Head/Dept Head)
        $level3Survey = $surveys[3] ?? null;
        if ($level3Survey) {
            $approverIds = $this->resolveLevel3ApproverIds(array_map('intval', $participants));
            foreach ($approverIds as $approverId) {
                SurveyResponse::firstOrCreate([
                    'survey_id' => $level3Survey->id,
                    'employee_id' => $approverId,
                ]);
            }
        }
    }

    /**
     * Resolve Level 3 approver IDs for a list of participants.
     */
    public function resolveLevel3ApproverIds(array $participantIds): array
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

        // Default: employee
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
}
