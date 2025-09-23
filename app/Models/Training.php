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
        return $this->hasMany(TrainingAssesment::class);
    }

    /**
     * Get the attendances through sessions.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class, 'session_id');
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

    /**
     * Scope to get trainings by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get trainings by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
