<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trainer extends Model
{
    protected $table = 'trainer';

    protected $fillable = [
        'user_id',
        'name',
        'institution',
    ];

    /**
     * Get the user that owns the trainer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the trainer competencies for the trainer.
     */
    public function trainerCompetencies(): HasMany
    {
        return $this->hasMany(TrainerCompetency::class, 'trainer_id');
    }

    /**
     * Get the competencies for the trainer.
     */
    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'trainer_competency', 'trainer_id', 'competency_id');
    }

    /**
     * Get the training sessions conducted by the trainer.
     */
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'trainer_id');
    }
}
