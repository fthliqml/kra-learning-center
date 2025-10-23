<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SurveyQuestion extends Model
{
    protected $table = "survey_questions";

    protected $fillable = [
        'text',
        'order',
    ];

    /**
     * Summary of option
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SurveyOption, SurveyQuestion>
     */
    public function option()
    {
        return $this->hasMany(SurveyOption::class);
    }
}
