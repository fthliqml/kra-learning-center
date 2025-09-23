<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    protected $table = 'training_sessions';

    protected $fillable = [
        'training_id',
        'trainer_id',
        'room_name',
        'room_location',
        'start_time',
        'end_time',
        'day_number',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'day_number' => 'integer',
    ];

    /**
     * Get the training that owns the session.
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the trainer that conducts the session.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    /**
     * Get the attendances for the session.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class, 'session_id');
    }

    /**
     * Scope to get sessions by day number.
     */
    public function scopeByDay($query, $dayNumber)
    {
        return $query->where('day_number', $dayNumber);
    }

    /**
     * Scope to get sessions by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
