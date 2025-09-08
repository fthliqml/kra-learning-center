<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    protected $table = 'training_modules';

    protected $fillable = [
        'title',
        'group_comp',
        'duration',
        'frequency',
    ];
}
