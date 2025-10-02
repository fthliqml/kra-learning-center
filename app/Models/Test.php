<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
  protected $fillable = [
    'course_id',
    'type',
    'passing_score',
    'time_limit',
    'max_attempts',
    'randomize_question',
    'is_active',
  ];

  protected $casts = [
    'passing_score' => 'integer',
    'time_limit' => 'integer',
    'max_attempts' => 'integer',
    'randomize_question' => 'boolean',
    'is_active' => 'boolean',
  ];

  public function course(): BelongsTo
  {
    return $this->belongsTo(Course::class);
  }

  public function questions(): HasMany
  {
    return $this->hasMany(TestQuestion::class)->orderBy('order')->orderBy('id');
  }
}
