<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssignment extends Model
{
    protected $table = 'course_assignments';

    protected $fillable = [
        'course_id',
        'trainer_id',
        'employee_id',
        'training_session_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the course for the assignment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the trainer for the assignment.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    /**
     * Get the employee for the assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the training session for the assignment.
     */
    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    /**
     * Get the user courses for the assignment.
     */
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class, 'assignment_id');
    }

    /**
     * Scope to get assignments for a specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get assignments by trainer.
     */
    public function scopeByTrainer($query, $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }
}
