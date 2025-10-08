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
 * @method bool hasRole(string $role)
 * @method bool hasAnyRole(array|string $roles)
 * @method bool hasAllRoles(array|string $roles)
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
     * Direct many-to-many courses via user_courses pivot.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'user_courses', 'user_id', 'course_id')
            ->withTimestamps();
    }

    /* =====================
     |  Role Utilities
     |  Single role stored on users.role (string)
     |===================== */
    public function hasRole(string $role): bool
    {
        return strtolower(trim($this->role ?? '')) === strtolower(trim($role));
    }

    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        $current = strtolower(trim($this->role ?? ''));
        foreach ($roles as $r) {
            if ($current === strtolower(trim($r)))
                return true;
        }
        return false;
    }

    public function hasAllRoles(array|string $roles): bool
    {
        // Because we only store one role string, "all roles" only true if exactly one requested and it matches.
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        $roles = array_values(array_filter(array_map(fn($r) => trim($r), $roles)));
        return count($roles) === 1 && $this->hasRole($roles[0]);
    }
}
