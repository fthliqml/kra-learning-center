<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $table = 'survey_responses';

    protected $fillable = [
        'survey_id',
        'employee_id',
        'is_completed',
        'submitted_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function survey()
    {
        return $this->belongsTo(TrainingSurvey::class, 'survey_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
