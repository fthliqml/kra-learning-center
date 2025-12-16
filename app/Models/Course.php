<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Section;
use App\Models\Test;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'title',
        'description',
        'thumbnail_url',
        'competency_id',
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
     * Get the trainings for the course.
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class, 'course_id');
    }

    /**
     * Get the competency associated with this course.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
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
        $today = Carbon::today();
        return $query->whereHas('trainings', function ($t) use ($userId, $today) {
            $t
                // ->where(function ($w) use ($today) {
                //     $w->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                // })
                // ->where(function ($w) use ($today) {
                //     $w->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                // })
                ->whereHas('assessments', function ($a) use ($userId) {
                    $a->where('employee_id', $userId);
                });
        });
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

        // Total progress units according to business rules
        $units = $this->progressUnitsCount();

        if ($units <= 0) {
            return 0;
        }

        $current = (int) ($assignment->current_step ?? 0);
        return min(100, max(0, (int) floor(($current / max(1, $units)) * 100)));
    }

    /**
     * Count total units for progress:
     * - +1 if pretest exists
     * - +N for each Section across topics (fallback to topics if no sections)
     * - +1 if posttest exists
     * Result stage does not count.
     */
    public function progressUnitsCount(): int
    {
        $units = 0;
        // Pretest unit
        $hasPretest = Test::where('course_id', $this->id)->where('type', 'pretest')->exists();
        if ($hasPretest)
            $units += 1;

        // Sections as main units (fallback to topics count)
        $sectionCount = Section::whereHas('topic', fn($q) => $q->where('course_id', $this->id))->count();
        if ($sectionCount > 0) {
            $units += $sectionCount;
        } else {
            $units += $this->learningModules()->count();
        }

        // Posttest unit: business rule says posttest is always present, count it as a unit
        $units += 1;

        return (int) $units;
    }

    /**
     * Check if course has all required content (ready to be published/inactive).
     */
    public function isComplete(): bool
    {
        // Must have basic info
        if (empty($this->title) || empty($this->description) || empty($this->competency_id)) {
            return false;
        }

        // Must have pretest with at least 1 question
        $pretest = $this->tests()->where('type', 'pretest')->first();
        if (!$pretest) {
            return false;
        }
        $pretestQuestions = $pretest->questions()->count();
        if ($pretestQuestions === 0) {
            return false;
        }

        // Must have at least 1 learning module (topic) with at least 1 section
        $topicCount = $this->learningModules()->count();
        if ($topicCount === 0) {
            return false;
        }
        $sectionCount = Section::whereHas('topic', fn($q) => $q->where('course_id', $this->id))->count();
        if ($sectionCount === 0) {
            return false;
        }

        // Must have posttest with at least 1 question
        $posttest = $this->tests()->where('type', 'posttest')->first();
        if (!$posttest) {
            return false;
        }
        $posttestQuestions = $posttest->questions()->count();
        if ($posttestQuestions === 0) {
            return false;
        }

        // Must have test configuration
        if (!$pretest->passing_score && $pretest->passing_score !== 0) {
            return false;
        }
        if (!$posttest->passing_score) {
            return false;
        }

        return true;
    }
}
