<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyOption extends Model
{
    protected $fillable = [
        'question_id',
        'text',
        'order',
    ];

    protected $casts = [
        'question_id' => 'integer'
    ];

    /**
     * Summary of question
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SurveyQuestion, SurveyOption>
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class);
    }
}
