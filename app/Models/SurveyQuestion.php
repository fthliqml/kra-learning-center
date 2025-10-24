<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SurveyQuestion extends Model
{
    protected $table = "survey_questions";

    protected $fillable = [
        'text',
        'question_type',
        'order',
    ];

    /**
     * Summary of option
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SurveyOption, SurveyQuestion>
     */
    public function options()
    {
        return $this->hasMany(SurveyOption::class, 'question_id');
    }
}
