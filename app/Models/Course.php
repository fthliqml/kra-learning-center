<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'title',
        'description',
        'thumbnail_url',
        'group_comp',
        'status',
    ];

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
     * Get the topics for the course.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'course_id');
    }

    /**
     * Alias: treat topics as learning modules for progress logic.
     */
    public function learningModules(): HasMany
    {
        return $this->hasMany(Topic::class, 'course_id');
    }

    /**
     * Tests (pretest/posttest) associated with this course.
     */
    public function tests(): HasMany
    {
        return $this->hasMany(Test::class, 'course_id');
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

    /**
     * Compute progress percentage for a specific user (or current auth user).
     */
    public function progressForUser(?User $user = null): int
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return 0;
        }

        // Expect userCourses filtered; fallback search by user id.
        $assignment = $this->relationLoaded('userCourses')
            ? $this->userCourses->firstWhere('user_id', $user->id)
            : $this->userCourses()->where('user_id', $user->id)->select(['id', 'user_id', 'course_id', 'current_step'])->first();

        if (!$assignment) {
            return 0;
        }

        // Prefer pre-counted attribute
        $modules = null;
        if (isset($this->learning_modules_count)) {
            $modules = (int) $this->learning_modules_count;
        } elseif ($this->relationLoaded('learningModules')) {
            $modules = $this->learningModules->count();
        } else {
            $modules = $this->learningModules()->count();
        }

        if ($modules <= 0) {
            return 0;
        }

        $current = (int) ($assignment->current_step ?? 0);
        return min(100, max(0, (int) floor(($current / max(1, $modules)) * 100)));
    }
}
