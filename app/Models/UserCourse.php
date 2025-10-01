<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserCourse extends Model
{
    protected $table = 'user_courses';

    protected $fillable = [
        'user_id',
        'course_id',
        'current_step',
        'status',
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    /**
     * Get the user for the course enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course for the enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Calculate progress percentage.
     */
    public function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalModules = $this->course?->learningModules()->count() ?? 0;
                if ($totalModules <= 0) {
                    return 0;
                }
                $raw = ($this->current_step / $totalModules) * 100;
                return min(100, max(0, (int) round($raw)));
            }
        );
    }
}
