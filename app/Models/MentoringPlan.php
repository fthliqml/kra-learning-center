<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentoringPlan extends Model
{
    use HasFactory;

    protected $table = 'mentoring_plans';

    protected $fillable = [
        'user_id',
        'mentor_id',
        'objective',
        'method',
        'frequency',
        'duration',
    ];

    protected $casts = [
        'frequency' => 'integer',
        'duration' => 'integer',
    ];

    /**
     * Get the user that owns this mentoring plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mentor for this mentoring plan.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
