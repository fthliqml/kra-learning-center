<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyTemplateDefault extends Model
{
  protected $table = 'survey_template_defaults';

  protected $fillable = [
    'survey_template_id',
    'level',
  ];

  protected $casts = [
    'level' => 'integer',
  ];

  /**
   * Get the survey template.
   */
  public function surveyTemplate(): BelongsTo
  {
    return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
  }

  /**
   * Get the default template for a specific level.
   */
  public static function getDefaultTemplate(int $level = 1): ?SurveyTemplate
  {
    $default = self::where('level', $level)->first();
    return $default?->surveyTemplate;
  }

  // Removed group comp logic

  /**
   * Set default template for a level.
   */
  public static function setDefaultForLevel(int $templateId, int $level = 1): void
  {
    // Remove existing default for this level
    self::where('level', $level)->delete();
    // Create new default
    self::create([
      'survey_template_id' => $templateId,
      'level' => $level,
    ]);
  }
}
