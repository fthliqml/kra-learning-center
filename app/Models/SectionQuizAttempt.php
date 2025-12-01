<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionQuizAttempt extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'section_id',
    'score',
    'total_questions',
    'status',
    'passed',
    'started_at',
    'completed_at',
  ];

  protected $casts = [
    'score' => 'integer',
    'total_questions' => 'integer',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
    'passed' => 'boolean',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function section()
  {
    return $this->belongsTo(Section::class);
  }

  public function answers()
  {
    return $this->hasMany(SectionQuizAttemptAnswer::class, 'quiz_attempt_id');
  }
}
