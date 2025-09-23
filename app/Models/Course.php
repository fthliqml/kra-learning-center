<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'created_by',
        'edited_by',
        'training_id',
        'title',
        'code',
        'description',
        'group_comp',
        'thumbnail_url',
        'duration',
        'frequency',
        'status',
    ];

    protected $casts = [
        'duration' => 'integer',
        'frequency' => 'integer',
    ];

    /**
     * Get the user who created the course.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last edited the course.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Get the training associated with the course.
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    /**
     * Get the learning modules for the course.
     */
    public function learningModules(): HasMany
    {
        return $this->hasMany(LearningModule::class, 'course_id');
    }

    /**
     * Get the course assignments for the course.
     */
    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'course_id');
    }

    /**
     * Get the user courses for the course.
     */
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class, 'course_id');
    }

    /**
     * Get the users enrolled in the course.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_courses');
    }

    /**
     * Scope to get active courses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get courses by group competency.
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group_comp', $group);
    }
}
