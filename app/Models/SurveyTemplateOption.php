<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyTemplateOption extends Model
{
  protected $table = 'survey_template_options';
  protected $fillable = [
    'survey_template_question_id',
    'text',
    'order',
  ];

  public function question()
  {
    return $this->belongsTo(SurveyTemplateQuestion::class, 'survey_template_question_id');
  }
}
