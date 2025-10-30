<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
  protected $table = 'survey_answers';

  protected $fillable = [
    'response_id',
    'question_id',
    'selected_option_id',
    'essay_answer',
  ];

  public function response()
  {
    return $this->belongsTo(SurveyResponse::class, 'response_id');
  }

  public function question()
  {
    return $this->belongsTo(SurveyQuestion::class, 'question_id');
  }

  public function selectedOption()
  {
    return $this->belongsTo(SurveyOption::class, 'selected_option_id');
  }
}
