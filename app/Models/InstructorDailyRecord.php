<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorDailyRecord extends Model
{
    protected $fillable = [
        'instructor_id',
        'date',
        'code',
        'activity',
        'remarks',
        'hour',
    ];

    protected $casts = [
        'date' => 'date',
        'hour' => 'decimal:1',
    ];

    /**
     * Get the instructor (user) that owns the record
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Scope to filter by instructor
     */
    public function scopeForInstructor($query, $userId)
    {
        return $query->where('instructor_id', $userId);
    }
}
