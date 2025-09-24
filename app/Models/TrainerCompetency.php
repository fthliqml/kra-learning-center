<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerCompetency extends Model
{
    protected $table = 'trainer_competency';

    protected $fillable = [
        'trainer_id',
        'competency_id',
    ];

    /**
     * Get the trainer that owns the competency.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    /**
     * Get the competency that belongs to the trainer.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }
}
