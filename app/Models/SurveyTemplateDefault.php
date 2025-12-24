<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyTemplateDefault extends Model
{
  protected $table = 'survey_template_defaults';

  protected $fillable = [
    'survey_template_id',
    'group_comp',
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
   * Get the default template for a specific group_comp and level.
   */
  public static function getDefaultTemplate(string $groupComp, int $level = 1): ?SurveyTemplate
  {
    $default = self::where('group_comp', $groupComp)
      ->where('level', $level)
      ->first();

    return $default?->surveyTemplate;
  }

  /**
   * Get all group comps that use a specific template.
   */
  public static function getGroupCompsForTemplate(int $templateId, int $level = 1): array
  {
    return self::where('survey_template_id', $templateId)
      ->where('level', $level)
      ->pluck('group_comp')
      ->toArray();
  }

  /**
   * Set default template for group comps.
   */
  public static function setDefaultForGroupComps(int $templateId, array $groupComps, int $level = 1): void
  {
    // Remove existing defaults for this template at this level
    self::where('survey_template_id', $templateId)
      ->where('level', $level)
      ->delete();

    // Remove existing defaults for these group_comps at this level (to ensure one template per group)
    self::whereIn('group_comp', $groupComps)
      ->where('level', $level)
      ->delete();

    // Create new defaults
    foreach ($groupComps as $groupComp) {
      self::create([
        'survey_template_id' => $templateId,
        'group_comp' => $groupComp,
        'level' => $level,
      ]);
    }
  }
}
