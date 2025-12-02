<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyTemplate extends Model
{
  protected $table = 'survey_templates';

  protected $fillable = [
    'title',
    'description',
    'status',
    'level',
  ];

  protected $casts = [
    'level' => 'integer',
  ];

  public function questions()
  {
    return $this->hasMany(SurveyTemplateQuestion::class, 'survey_template_id');
  }
}
