<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingPlan extends Model
{
    use HasFactory;

    protected $table = 'training_plans';

    /**
     * Status Flow:
     * - draft: Initial state, not submitted
     * - pending_spv: Submitted, waiting for SPV approval
     * - rejected_spv: Rejected by SPV, needs revision
     * - pending_leader: Approved by SPV, waiting for Leader LID approval
     * - rejected_leader: Rejected by Leader LID, needs revision
     * - approved: Fully approved by both SPV and Leader LID
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_SPV = 'pending_spv';
    const STATUS_REJECTED_SPV = 'rejected_spv';
    const STATUS_PENDING_LEADER = 'pending_leader';
    const STATUS_REJECTED_LEADER = 'rejected_leader';
    const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id',
        'competency_id',
        'status',
        'year',
        'approved_by',
        'approved_at',
        'spv_approved_by',
        'spv_approved_at',
        'leader_approved_by',
        'leader_approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'year' => 'integer',
        'approved_at' => 'datetime',
        'spv_approved_at' => 'datetime',
        'leader_approved_at' => 'datetime',
    ];

    /**
     * Check if this plan can be edited by the employee.
     * Editable when: draft, pending_spv, rejected_spv, or rejected_leader
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_SPV,
            self::STATUS_REJECTED_SPV,
            self::STATUS_REJECTED_LEADER,
        ]);
    }

    /**
     * Check if this plan can be approved by SPV.
     */
    public function canApproveBySpv(): bool
    {
        return $this->status === self::STATUS_PENDING_SPV;
    }

    /**
     * Check if this plan can be approved by Leader LID.
     */
    public function canApproveByLeader(): bool
    {
        return $this->status === self::STATUS_PENDING_LEADER;
    }

    /**
     * Check if plan is waiting for any approval.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_SPV,
            self::STATUS_PENDING_LEADER,
        ]);
    }

    /**
     * Check if plan was rejected at any level.
     */
    public function isRejected(): bool
    {
        return in_array($this->status, [
            self::STATUS_REJECTED_SPV,
            self::STATUS_REJECTED_LEADER,
        ]);
    }

    /**
     * Get the SPV approver for this plan.
     */
    public function spvApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spv_approved_by');
    }

    /**
     * Get the Leader approver for this plan.
     */
    public function leaderApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_approved_by');
    }

    /**
     * Get the approver for this plan (legacy support).
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
