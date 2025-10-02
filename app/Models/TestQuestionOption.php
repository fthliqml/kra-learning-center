<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestionOption extends Model
{
  protected $fillable = [
    'question_id',
    'text',
    'order',
    'is_correct',
  ];

  protected $casts = [
    'order' => 'integer',
    'is_correct' => 'boolean',
  ];

  public function question(): BelongsTo
  {
    return $this->belongsTo(TestQuestion::class, 'question_id');
  }
}
