<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAssesment extends Model
{
    protected $fillable = [
        'training_id',
        'employee_id',
        'pretest_score',
        'posttest_score',
        'practical_score',
        'status',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
