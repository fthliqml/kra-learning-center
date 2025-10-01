<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionQuizAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_quiz_attempt_id',
        'section_quiz_question_id',
        'selected_option_id',
        'answer_text',
        'is_correct',
        'points_awarded',
        'order_index',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(SectionQuizAttempt::class, 'section_quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(SectionQuizQuestion::class, 'section_quiz_question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(SectionQuizQuestionOption::class, 'selected_option_id');
    }
}
