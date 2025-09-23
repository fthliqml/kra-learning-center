<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        return $this->hasMany(TrainingAssesment::class, 'employee_id');
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
        return $this->belongsToMany(Course::class, 'user_courses');
    }
}
