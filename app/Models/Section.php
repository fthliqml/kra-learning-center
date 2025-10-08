<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
  protected $fillable = [
    'topic_id',
    'title',
    'is_quiz_on',
  ];

  protected $casts = [
    'is_quiz_on' => 'boolean',
  ];

  public function topic(): BelongsTo
  {
    return $this->belongsTo(Topic::class);
  }

  public function resources(): HasMany
  {
    return $this->hasMany(ResourceItem::class, 'section_id');
  }

  public function quizQuestions(): HasMany
  {
    return $this->hasMany(SectionQuizQuestion::class, 'section_id')->orderBy('order');
  }
}
