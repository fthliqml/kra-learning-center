<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\TestAttempt;
use Carbon\Carbon;

class Training extends Model
{
    protected $fillable = [
        'course_id',
        'module_id',
        'competency_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'status',
        'section_head_signed_by',
        'section_head_signed_at',
        'dept_head_signed_by',
        'dept_head_signed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'section_head_signed_at' => 'datetime',
        'dept_head_signed_at' => 'datetime',
    ];

    public function sectionHeadSigner()
    {
        return $this->belongsTo(User::class, 'section_head_signed_by');
    }

    public function deptHeadSigner()
    {
        return $this->belongsTo(User::class, 'dept_head_signed_by');
    }

    /**
     * Get the training sessions for the training.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * Get the training assessments for the training.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(TrainingAssessment::class);
    }

    /**
     * Get the attendances through sessions.
     */
    public function attendances()
    {
        return $this->hasManyThrough(
            TrainingAttendance::class,
            TrainingSession::class,
            'training_id',   // FK di training_sessions yang menunjuk trainings
            'session_id',    // FK di training_attendances yang menunjuk training_sessions
            'id',            // PK di trainings
            'id'             // PK di training_sessions
        );
    }

    /**
     * Link to an optional primary course (nullable FK in migration).
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Link to an optional training module (nullable FK in migration).
     */
    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }

    public function groupComp(): Attribute
    {
        return Attribute::make(
            get: function () {
                $group = $this->competency?->type;

                if (!$group && ($this->type === 'IN' || $this->type === null)) {
                    $group = $this->module?->competency?->type;
                }

                if (!$group && in_array($this->type, ['LMS', 'BLENDED'])) {
                    $group = $this->course?->competency?->type;
                }

                return $group;
            }
        );
    }

    /**
     * Get the duration of the training in days.
     */
    public function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->start_date && $this->end_date) {
                    return Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
                }
                return 1;
            }
        );
    }

    /**
     * Check if all participants have passed the post-test and mark training as done.
     * Only applies to LMS, BLENDED, and IN types. OUT is manual.
     * 
     * @return bool True if training was marked as done
     */
    public function checkAndMarkAsDone(): bool
    {
        // Skip if already done or type is OUT (manual)
        if ($this->status === 'done' || $this->type === 'OUT') {
            return false;
        }

        // Get post-test based on training type
        $posttest = null;
        if (in_array($this->type, ['LMS', 'BLENDED']) && $this->course) {
            $posttest = $this->course->tests()->where('type', 'posttest')->first();
        } elseif ($this->type === 'IN' && $this->module) {
            $posttest = $this->module->posttest;
        }

        if (!$posttest) {
            return false;
        }

        // Get all assigned participant IDs
        $participantIds = $this->assessments()->pluck('employee_id')->toArray();
        
        if (empty($participantIds)) {
            return false;
        }

        // Check if ALL participants have at least one passed attempt
        $passedParticipantIds = TestAttempt::where('test_id', $posttest->id)
            ->whereIn('user_id', $participantIds)
            ->where('is_passed', true)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        // Compare: all participants must have passed
        $allPassed = count($passedParticipantIds) === count($participantIds)
            && empty(array_diff($participantIds, $passedParticipantIds));

        if ($allPassed) {
            $this->update(['status' => 'done']);
            return true;
        }

        return false;
    }
}
