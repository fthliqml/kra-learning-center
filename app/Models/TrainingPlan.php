<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingPlan extends Model
{
    use HasFactory;

    protected $table = 'training_plans';

    protected $fillable = [
        'user_id',
        'competency_id',
        'status',
        'year',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Check if this plan can be edited (only draft or pending status)
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    /**
     * Get the approver for this plan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user that owns this training plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the competency associated with this training plan.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
