<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionQuizAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'quiz_question_id',
        'selected_option_id',
        'answer_text',
        'is_correct',
        'points_awarded',
        'order',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(SectionQuizAttempt::class, 'quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(SectionQuizQuestion::class, 'quiz_question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(SectionQuizQuestionOption::class, 'selected_option_id');
    }
}
