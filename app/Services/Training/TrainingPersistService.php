<?php

namespace App\Services\Training;

use App\Models\Course;
use App\Models\Competency;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for persisting Training data (create, update, delete).
 * Extracted from TrainingFormModal for cleaner separation of concerns.
 */
class TrainingPersistService
{
    /**
     * Create a new training with all related data.
     *
     * @param array $data Form data
     * @param array $participants Array of participant user IDs
     * @param SessionSyncService $sessionService
     * @return Training
     */
    public function create(array $data, array $participants, SessionSyncService $sessionService): Training
    {
        return DB::transaction(function () use ($data, $participants, $sessionService) {
            // Resolve training name
            $trainingName = $this->resolveTrainingName($data);

            // Create training record
            $training = Training::create([
                'name' => $trainingName,
                'type' => $data['training_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'course_id' => in_array($data['training_type'], ['LMS', 'BLENDED']) ? $data['course_id'] : null,
                'module_id' => $data['training_type'] === 'IN' ? $data['module_id'] : null,
                'competency_id' => $data['training_type'] === 'OUT' ? $data['competency_id'] : null,
            ]);

            // Note: Surveys are NOT created here. They will be created when training is closed.\n            // This ensures surveys only exist for completed trainings.

            // Create sessions
            $sessions = $sessionService->createSessionsForTraining(
                $training,
                $data['start_date'],
                $data['end_date'],
                $data['training_type'],
                $data['trainer_id'] ?? null,
                $data['room'] ?? ['name' => '', 'location' => ''],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );

            // Create assessments for participants
            foreach ($participants as $participantId) {
                TrainingAssessment::create([
                    'training_id' => $training->id,
                    'employee_id' => $participantId,
                ]);
            }

            // Create attendance records (skip for LMS)
            if ($data['training_type'] !== 'LMS') {
                foreach ($sessions as $session) {
                    foreach ($participants as $participantId) {
                        TrainingAttendance::create([
                            'session_id' => $session->id,
                            'employee_id' => $participantId,
                            'notes' => null,
                            'recorded_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            return $training;
        });
    }

    /**
     * Update an existing training with all related data.
     *
     * @param int $trainingId
     * @param array $data Form data
     * @param array $participants Array of participant user IDs
     * @param SessionSyncService $sessionService
     * @return Training|null
     */
    public function update(int $trainingId, array $data, array $participants, SessionSyncService $sessionService): ?Training
    {
        return DB::transaction(function () use ($trainingId, $data, $participants, $sessionService) {
            $training = Training::with('sessions')->find($trainingId);
            if (!$training) {
                return null;
            }

            $originalType = $training->type;
            $originalStart = $training->start_date ? Carbon::parse($training->start_date)->toDateString() : null;
            $originalEnd = $training->end_date ? Carbon::parse($training->end_date)->toDateString() : null;

            // Update main training fields
            $this->updateTrainingFields($training, $data);

            // Check if sessions need to be rebuilt
            $dateChanged = ($originalStart !== $data['start_date']) || ($originalEnd !== $data['end_date']);
            $typeChanged = $originalType !== $data['training_type'];

            if ($dateChanged || $typeChanged) {
                $sessionService->rebuildSessions(
                    $training,
                    $data['start_date'],
                    $data['end_date'],
                    $originalType,
                    $data['training_type'],
                    $data['trainer_id'] ?? null,
                    $data['room'] ?? ['name' => '', 'location' => ''],
                    $data['start_time'] ?? null,
                    $data['end_time'] ?? null
                );
            } else {
                $sessionService->updateSessionFields(
                    $training,
                    $data['training_type'],
                    $data['trainer_id'] ?? null,
                    $data['room'] ?? ['name' => '', 'location' => ''],
                    $data['start_time'] ?? null,
                    $data['end_time'] ?? null
                );
            }

            // Update participants and survey responses
            $sessionService->updateParticipantsAndSurveyResponses($training, $participants, $data['training_type']);

            // Update attendance
            $sessionService->updateAttendance($training, $participants, $data['training_type']);

            return $training;
        });
    }

    /**
     * Delete a training and all related data.
     *
     * @param int $trainingId
     * @return bool
     */
    public function delete(int $trainingId): bool
    {
        return DB::transaction(function () use ($trainingId) {
            $training = Training::with('sessions')->find($trainingId);
            if (!$training) {
                return false;
            }

            $sessionIds = $training->sessions->pluck('id')->all();
            
            if (!empty($sessionIds)) {
                // Delete attendances under sessions
                TrainingAttendance::whereIn('session_id', $sessionIds)->delete();
            }
            
            // Delete sessions
            TrainingSession::where('training_id', $trainingId)->delete();
            
            // Delete assessments
            TrainingAssessment::where('training_id', $trainingId)->delete();
            
            // Finally delete training
            $training->delete();

            return true;
        });
    }

    /**
     * Resolve the training name based on type and data.
     */
    private function resolveTrainingName(array $data): string
    {
        $type = $data['training_type'];
        
        if (in_array($type, ['LMS', 'BLENDED'])) {
            // Use course title
            if (!empty($data['course_id'])) {
                $course = Course::find($data['course_id']);
                return $course?->title ?? $type;
            }
            return $type;
        }
        
        if ($type === 'OUT') {
            // Use training name or fallback to competency name
            $name = trim($data['training_name'] ?? '');
            if ($name === '' && !empty($data['competency_id'])) {
                $competency = Competency::find($data['competency_id']);
                return $competency?->name ?? 'Training';
            }
            return $name !== '' ? $name : 'Training';
        }
        
        // IN type or default
        return $data['training_name'] ?? 'Training';
    }

    /**
     * Update main training fields.
     */
    private function updateTrainingFields(Training $training, array $data): void
    {
        $type = $data['training_type'];
        $training->type = $type;

        if (in_array($type, ['LMS', 'BLENDED'])) {
            $training->course_id = $data['course_id'];
            $training->module_id = null;
            $training->competency_id = null;
            
            // Resolve course title
            if (!empty($data['course_id'])) {
                $course = Course::find($data['course_id']);
                $training->name = $course?->title ?? $training->name;
            }
        } elseif ($type === 'IN') {
            $training->course_id = null;
            $training->module_id = $data['module_id'];
            $training->competency_id = null;
            $training->name = $data['training_name'];
        } else {
            // OUT type
            $training->course_id = null;
            $training->module_id = null;
            $training->competency_id = $data['competency_id'];
            
            $name = trim($data['training_name'] ?? '');
            if ($name === '' && !empty($data['competency_id'])) {
                $competency = Competency::find($data['competency_id']);
                $name = $competency?->name ?? '';
            }
            $training->name = $name !== '' ? $name : $training->name;
        }

        $training->start_date = $data['start_date'];
        $training->end_date = $data['end_date'];
        $training->save();
    }
}
