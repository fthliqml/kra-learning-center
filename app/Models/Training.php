<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Training extends Model
{
    protected $fillable = [
        'name',
        'type',
        'group_comp',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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
     * Get the courses associated with the training.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'training_id');
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
}
