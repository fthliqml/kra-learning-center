<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerCompetency extends Model
{
    protected $table = 'trainer_competency';

    protected $fillable = [
        'trainer_id',
        'competency_id',
    ];

        // Relasi ke Trainer
        public function trainer()
        {
            return $this->belongsTo(Trainer::class, 'trainer_id');
        }

        // Relasi ke Competency
        public function competency()
        {
            return $this->belongsTo(Competency::class, 'competency_id');
        }
}
