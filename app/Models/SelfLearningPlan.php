<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfLearningPlan extends Model
{
    use HasFactory;

    protected $table = 'self_learning_plans';

    protected $fillable = [
        'user_id',
        'mentor_id',
        'title',
        'objective',
        'start_date',
        'end_date',
        'status',
        'year',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
     * Get the user that owns this self learning plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mentor for this self learning plan.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
