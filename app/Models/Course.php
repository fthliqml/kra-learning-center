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
        'training_id',
        'title',
        'description',
        'thumbnail_url',
        'group_comp',
        'status',
    ];

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
        return $this->belongsToMany(User::class, 'user_courses', 'course_id', 'user_id');
    }

    /**
     * Scope: only courses assigned to a given user id.
     */
    public function scopeAssignedToUser($query, $userId)
    {
        if (!$userId) {
            // If no user, return empty result intentionally
            return $query->whereRaw('1 = 0');
        }
        return $query->whereHas('userCourses', fn($q) => $q->where('user_id', $userId));
    }
}
