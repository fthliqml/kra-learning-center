<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    protected $table = 'training_modules';

    protected $fillable = [
        'title',
        'group_comp',
        'objective',
        'training_content',
        'method',
        'duration',
        'frequency',
    ];

    protected $casts = [
        'duration' => 'integer',
        'frequency' => 'integer',
    ];
}
