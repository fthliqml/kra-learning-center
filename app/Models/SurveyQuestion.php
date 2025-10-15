<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SurveyQuestion extends Model
{
    protected $table = "survey_questions";

    protected $fillable = [
        'template_id',
        'text',
        'order',
    ];

    /**
     * Summary of surveyTemplate
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SurveyTemplate, SurveyQuestion>
     */
    public function surveyTemplate()
    {
        return $this->belongsTo(SurveyTemplate::class);
    }

    /**
     * Summary of option
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SurveyOption, SurveyQuestion>
     */
    public function option()
    {
        return $this->hasMany(SurveyOption::class);
    }
}
