<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyTemplateQuestion extends Model
{
  protected $table = 'survey_template_questions';
  protected $fillable = [
    'survey_template_id',
    'text',
    'question_type',
    'order',
  ];

  public function template()
  {
    return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
  }

  public function options()
  {
    return $this->hasMany(SurveyTemplateOption::class, 'survey_template_question_id');
  }
}
