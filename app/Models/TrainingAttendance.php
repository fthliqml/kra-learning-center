<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAttendance extends Model
{
    protected $table = 'training_attendances';

    protected $fillable = [
        'session_id',
        'employee_id',
        'status',
        'notes',
        'recorded_at',
    ];

    // Relationships
    public function session()
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
