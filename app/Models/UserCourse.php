<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserCourse extends Model
{
    protected $table = 'user_courses';

    protected $fillable = [
        'user_id',
        'course_id',
        'assignment_id',
        'current_step',
        'status',
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    /**
     * Get the user for the course enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course for the enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the course assignment for the enrollment.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class, 'assignment_id');
    }

    /**
     * Calculate progress percentage.
     */
    public function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalModules = $this->course?->learningModules()->count() ?? 1;
                return ($this->current_step / $totalModules) * 100;
            }
        );
    }

    /**
     * Scope to get courses by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed courses.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get in-progress courses.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get not started courses.
     */
    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }
}
