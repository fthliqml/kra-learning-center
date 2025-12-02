<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyValue extends Model
{
    use HasFactory;

    protected $table = 'competency_values';

    protected $fillable = [
        'competency_id',
        'position',
        'bobot',
        'value',
    ];

    protected $casts = [
        'value' => 'integer',
    ];

    /**
     * Get the competency that owns this value.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
