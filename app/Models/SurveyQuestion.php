<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SurveyQuestion extends Model
{
    protected $table = "survey_questions";

    protected $fillable = [
        'training_survey_id',
        'text',
        'question_type',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Summary of trainingSurvey
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<TrainingSurvey, SurveyQuestion>
     */
    public function trainingSurvey()
    {
        return $this->belongsTo(TrainingSurvey::class, 'training_survey_id');
    }

    /**
     * Summary of options
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SurveyOption, SurveyQuestion>
     */
    public function options()
    {
        return $this->hasMany(SurveyOption::class, 'question_id');
    }
}
