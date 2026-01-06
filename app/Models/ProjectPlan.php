<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPlan extends Model
{
    use HasFactory;

    protected $table = 'project_plans';

    /**
     * Status Flow:
     * - draft: Initial state, not submitted
     * - pending_spv: Submitted, waiting for Supervisor approval
     * - rejected_spv: Rejected by Supervisor, needs revision
     * - pending_dept_head: Submitted, waiting for Dept Head approval (no SPV in area)
     * - rejected_dept_head: Rejected by Dept Head, needs revision
     * - pending_lid: Approved by area approver, waiting for Leader LID approval
     * - rejected_lid: Rejected by Leader LID, needs revision
     * - approved: Fully approved by both area approver and Leader LID
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_SPV = 'pending_spv';
    const STATUS_REJECTED_SPV = 'rejected_spv';
    const STATUS_PENDING_DEPT_HEAD = 'pending_dept_head';
    const STATUS_REJECTED_DEPT_HEAD = 'rejected_dept_head';
    const STATUS_PENDING_LID = 'pending_lid';
    const STATUS_REJECTED_LID = 'rejected_lid';
    const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id',
        'mentor_id',
        'name',
        'objective',
        'status',
        'year',
        'approved_by',
        'approved_at',
        'spv_approved_by',
        'spv_approved_at',
        'dept_head_approved_by',
        'dept_head_approved_at',
        'leader_approved_by',
        'leader_approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'year' => 'integer',
        'approved_at' => 'datetime',
        'spv_approved_at' => 'datetime',
        'dept_head_approved_at' => 'datetime',
        'leader_approved_at' => 'datetime',
    ];

    /**
     * Check if this plan can be edited by the employee.
     * Editable when: draft, pending_spv, rejected_spv, or rejected_lid
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_SPV,
            self::STATUS_REJECTED_SPV,
            self::STATUS_PENDING_DEPT_HEAD,
            self::STATUS_REJECTED_DEPT_HEAD,
            self::STATUS_REJECTED_LID,
        ]);
    }

    /**
     * Check if this plan can be approved by SPV.
     */
    public function canApproveBySpv(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_SPV,
            self::STATUS_PENDING_DEPT_HEAD,
        ]);
    }

    /**
     * Check if this plan can be approved by Leader LID.
     */
    public function canApproveByLeader(): bool
    {
        return $this->status === self::STATUS_PENDING_LID;
    }

    /**
     * Check if plan is waiting for any approval.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_SPV,
            self::STATUS_PENDING_DEPT_HEAD,
            self::STATUS_PENDING_LID,
        ]);
    }

    /**
     * Check if plan was rejected at any level.
     */
    public function isRejected(): bool
    {
        return in_array($this->status, [
            self::STATUS_REJECTED_SPV,
            self::STATUS_REJECTED_DEPT_HEAD,
            self::STATUS_REJECTED_LID,
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
     * Get the Dept Head approver for this plan.
     */
    public function deptHeadApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dept_head_approved_by');
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
     * Get the user that owns this project plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mentor for this project plan.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
