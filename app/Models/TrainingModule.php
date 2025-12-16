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
    ];

    protected $casts = [
        'duration' => 'integer',
        'frequency' => 'integer',
    ];

    /**
     * Get the competency associated with this training module.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
