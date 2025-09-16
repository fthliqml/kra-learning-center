<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    protected $table = 'competency';

    protected $fillable = [
        'description',
    ];

    // Relasi ke Trainer melalui tabel pivot trainer_competency
    public function trainers()
    {
        return $this->belongsToMany(Trainer::class, 'trainer_competency', 'competency_id', 'trainer_id');
    }
}
