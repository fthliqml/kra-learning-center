<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionQuizQuestion extends Model
{
    protected $fillable = [
        'section_id',
        'type',
        'question',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(SectionQuizQuestionOption::class, 'question_id');
    }
}
