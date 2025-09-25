<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'section',
        'NRP',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'NRP' => 'integer',
        ];
    }

    /**
     * Normalize NRP casing so $user->nrp works even if DB column is 'NRP'.
     */
    protected function nrp(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Support both 'NRP' and 'nrp' DB column names
                return $this->attributes['nrp'] ?? $this->attributes['NRP'] ?? null;
            },
        );
    }

    /**
     * Get the trainer profile for the user.
     */
    public function trainer(): HasOne
    {
        return $this->hasOne(Trainer::class);
    }

    /**
     * Get the training assessments for the user.
     */
    public function trainingAssessments(): HasMany
    {
        return $this->hasMany(TrainingAssessment::class, 'employee_id');
    }

    /**
     * Get the training attendances for the user.
     */
    public function trainingAttendances(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class, 'employee_id');
    }

    /**
     * Get the courses assigned to the user.
     */
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class);
    }

    /**
     * Get the courses the user is enrolled in.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'user_courses', 'user_id', 'course_id');
    }

    /**
     * Get the courses created by the user.
     */
    public function createdCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    /**
     * Get the courses last edited by the user.
     */
    public function editedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'edited_by');
    }

    /**
     * Get the course assignments for the user (as employee).
     */
    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'employee_id');
    }
}
