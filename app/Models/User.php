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

/**
 * @property string|null $role
 * @property string|null $position
 * @property string|null $section
 * @property string|null $department
 * @property string|null $division
 * @method bool hasRole(string $role)
 * @method bool hasAnyRole(array|string $roles)
 * @method bool hasAllRoles(array|string $roles)
 * @method bool hasPosition(string $position)
 * @method bool hasAnyPosition(array|string $positions)
 */
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
        'nrp',
        'email',
        'password',
        'section',
        'department',
        'division',
        'position',
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
            'nrp' => 'integer',
            'password' => 'hashed',
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
        return $this->hasMany(TrainingAssessment::class, 'employee_id');
    }

    /**
     * Get the user roles (instructor, certification, admin).
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
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
     * Direct many-to-many courses via user_courses pivot.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'user_courses', 'user_id', 'course_id')
            ->withTimestamps();
    }

    /**
     * Training requests created by this user.
     */
    public function trainingRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'created_by');
    }

    /**
     * Get the training plans for the user.
     */
    public function trainingPlans(): HasMany
    {
        return $this->hasMany(TrainingPlan::class);
    }

    /**
     * Get the self learning plans for the user.
     */
    public function selfLearningPlans(): HasMany
    {
        return $this->hasMany(SelfLearningPlan::class);
    }

    /**
     * Get the mentoring plans for the user.
     */
    public function mentoringPlans(): HasMany
    {
        return $this->hasMany(MentoringPlan::class);
    }

    /**
     * Get the project plans for the user.
     */
    public function projectPlans(): HasMany
    {
        return $this->hasMany(ProjectPlan::class);
    }

    /* =====================
     |  Position & Role Utilities
     |  Position: employee, supervisor, section_head, department_head, division_head, director
     |  Roles stored in user_roles table (instructor, certification, admin)
     |===================== */

    /**
     * Accessor for 'role' - returns position for backward compatibility
     * Maps position names to specific role names
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: function () {
                $position = $this->position ?? 'employee';
                // Map position to role names
                return match (strtolower($position)) {
                    'supervisor' => 'spv',
                    'section_head' => 'section_head',
                    'department_head' => 'dept_head',
                    'division_head' => 'div_head',
                    'director' => 'director',
                    default => 'employee',
                };
            }
        );
    }

    /**
     * Check if user has a specific position
     */
    public function hasPosition(string $position): bool
    {
        return strtolower(trim($this->position ?? '')) === strtolower(trim($position));
    }

    /**
     * Check if user has any of the given positions
     */
    public function hasAnyPosition(array|string $positions): bool
    {
        $positions = is_array($positions) ? $positions : explode(',', $positions);
        $positions = array_map(fn($p) => strtolower(trim($p)), $positions);
        return in_array(strtolower(trim($this->position ?? '')), $positions);
    }

    /**
     * Check if user has a specific functional role (from user_roles table)
     * Functional roles: instructor, certification, admin
     */
    public function hasRole(string $role): bool
    {
        return $this->userRoles()->where('role', strtolower(trim($role)))->exists();
    }

    /**
     * Check if user has any of the given functional roles
     */
    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        $roles = array_map(fn($r) => strtolower(trim($r)), $roles);
        return $this->userRoles()->whereIn('role', $roles)->exists();
    }

    /**
     * Check if user has all of the given functional roles
     */
    public function hasAllRoles(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        $roles = array_values(array_filter(array_map(fn($r) => strtolower(trim($r)), $roles)));

        $userRoles = $this->userRoles()->pluck('role')->map(fn($r) => strtolower(trim($r)))->toArray();

        foreach ($roles as $role) {
            if (!in_array($role, $userRoles)) {
                return false;
            }
        }
        return true;
    }
}
