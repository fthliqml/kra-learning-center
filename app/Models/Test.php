<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
  protected $fillable = [
    'course_id',
    'training_module_id',
    'type',
    'passing_score',
    'max_attempts',
    'show_result_immediately',
    'randomize_question',
  ];

  protected $casts = [
    'passing_score' => 'integer',
    'max_attempts' => 'integer',
    'randomize_question' => 'boolean',
    'show_result_immediately' => 'boolean',
  ];

  public function course(): BelongsTo
  {
    return $this->belongsTo(Course::class);
  }

  public function trainingModule(): BelongsTo
  {
    return $this->belongsTo(TrainingModule::class);
  }

  public function questions(): HasMany
  {
    return $this->hasMany(TestQuestion::class)->orderBy('order')->orderBy('id');
  }
}
