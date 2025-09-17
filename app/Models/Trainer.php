<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    protected $table = 'trainer';

    protected $fillable = [
        'user_id',
        'institution',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke tabel trainer_competency
    public function trainerCompetencies()
    {
        return $this->hasMany(TrainerCompetency::class, 'trainer_id');
    }

    // Relasi ke tabel competency melalui trainer_competency
    public function competencies()
    {
        return $this->belongsToMany(Competency::class, 'trainer_competency', 'trainer_id', 'competency_id');
    }
}
