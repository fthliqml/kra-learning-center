<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'status',
    ];

    public function sessions()
    {
        return $this->hasMany(TrainingSession::class);
    }
    public function assessments()
    {
        return $this->hasMany(TrainingAssesment::class);
    }
}
