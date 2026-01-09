<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingModule extends Model
{
    protected $table = 'training_modules';

    protected $fillable = [
        'title',
        'competency_id',
        'objective',
        'training_content',
        'method',
        'duration',
        'frequency',
        'theory_passing_score',
        'practical_passing_score',
    ];

    protected $casts = [
        'duration' => 'integer',
        'frequency' => 'integer',
        'theory_passing_score' => 'double',
        'practical_passing_score' => 'string',
    ];

    /**
     * Get the competency associated with this training module.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /**
     * Get all tests for this training module.
     */
    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    /**
     * Get the pretest for this training module.
     */
    public function pretest(): HasOne
    {
        return $this->hasOne(Test::class)->where('type', 'pretest');
    }

    /**
     * Get the posttest for this training module.
     */
    public function posttest(): HasOne
    {
        return $this->hasOne(Test::class)->where('type', 'posttest');
    }
}
