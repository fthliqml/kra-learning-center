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
}
