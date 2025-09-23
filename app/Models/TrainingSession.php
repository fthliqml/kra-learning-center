<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    protected $table = 'training_sessions';

    protected $fillable = [
        'training_id',
        'trainer_id',
        'room_name',
        'room_location',
        'start_time',
        'date',
        'end_time',
        'day_number',
        'status',
    ];

    // Relationships
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function attendances()
    {
        return $this->hasMany(TrainingAttendance::class, 'session_id');
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
}
