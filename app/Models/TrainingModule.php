<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
