<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestQuestion extends Model
{
  protected $fillable = [
    'test_id',
    'question_type',
    'text',
    'order',
    'max_points',
  ];

  protected $casts = [
    'order' => 'integer',
    'max_points' => 'integer',
  ];

  public function test(): BelongsTo
  {
    return $this->belongsTo(Test::class);
  }

  public function options(): HasMany
  {
    return $this->hasMany(TestQuestionOption::class, 'question_id')->orderBy('order')->orderBy('id');
  }
}
