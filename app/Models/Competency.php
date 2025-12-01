<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    protected $table = 'competency';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the trainers that have this competency.
     */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'trainer_competency', 'competency_id', 'trainer_id');
    }

    /**
     * Get the trainer competencies for this competency.
     */
    public function trainerCompetencies(): HasMany
    {
        return $this->hasMany(TrainerCompetency::class, 'competency_id');
    }
}
