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

    /**
     * Scope to get modules by group competency.
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group_comp', $group);
    }

    /**
     * Scope to get modules by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }
}
