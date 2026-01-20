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
        'competency_id',
        'training_module_id',
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

    public function trainingModule(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    /**
     * Check if this training plan has been realized.
     * A plan is realized if the employee has completed (passed) a training
     * with the same competency in the same year.
     */
    public function isRealized(): bool
    {
        if (!$this->user_id || !$this->competency_id || !$this->year) {
            return false;
        }

        $query = \App\Models\Training::query()
            ->where('status', 'done')
            ->whereYear('start_date', $this->year)
            ->whereNotNull('module_id')
            ->when($this->training_module_id, function ($q) {
                // If a specific module was chosen, match it exactly.
                $q->where('module_id', $this->training_module_id);
            }, function ($q) {
                // Otherwise match by competency.
                $q->whereHas('module', function ($qq) {
                    $qq->where('competency_id', $this->competency_id);
                });
            })
            ->whereHas('assessments', function ($q) {
                $q->where('employee_id', $this->user_id)
                    ->where('status', 'passed');
            });

        return $query->exists();
    }

    /**
     * Get the realized training for this plan.
     * Returns the first completed training that matches competency and employee passed.
     */
    public function getRealizedTraining()
    {
        if (!$this->user_id || !$this->competency_id || !$this->year) {
            return null;
        }

        return \App\Models\Training::query()
            ->where('status', 'done')
            ->whereYear('start_date', $this->year)
            ->whereNotNull('module_id')
            ->whereHas('module', function ($q) {
                $q->where('competency_id', $this->competency_id);
            })
            ->whereHas('assessments', function ($q) {
                $q->where('employee_id', $this->user_id)
                    ->where('status', 'passed');
            })
            ->first();
    }

    /**
     * Check if there's a scheduled training (in progress) for this plan.
     */
    public function hasScheduledTraining(): bool
    {
        if (!$this->user_id || !$this->competency_id || !$this->year) {
            return false;
        }

        return \App\Models\Training::query()
            ->whereIn('status', ['in_progress'])
            ->whereYear('start_date', $this->year)
            ->where(function ($q) {
                $q->whereHas('module', function ($m) {
                    $m->where('competency_id', $this->competency_id);
                })->orWhereHas('course', function ($c) {
                    $c->where('competency_id', $this->competency_id);
                });
            })
            ->whereHas('assessments', function ($q) {
                $q->where('employee_id', $this->user_id);
            })
            ->exists();
    }

    /**
     * Check if there's a closed/completed training for this plan (training already finished/closed).
     * This is used for realization badge display (not for pass/fail).
     */
    public function hasClosedTraining(): bool
    {
        if (!$this->user_id || !$this->competency_id || !$this->year) {
            return false;
        }

        return \App\Models\Training::query()
            ->whereIn('status', ['done', 'approved', 'rejected'])
            ->whereYear('start_date', $this->year)
            ->where(function ($q) {
                $q->whereHas('module', function ($m) {
                    $m->where('competency_id', $this->competency_id);
                })->orWhereHas('course', function ($c) {
                    $c->where('competency_id', $this->competency_id);
                });
            })
            ->whereHas('assessments', function ($q) {
                $q->where('employee_id', $this->user_id);
            })
            ->exists();
    }

    /**
     * Get realization status for display.
     * Returns: 'completed', 'scheduled', or 'waiting'
     */
    public function getRealizationStatus(): string
    {
        // Badge should reflect training lifecycle:
        // - completed: training already closed (done/approved/rejected)
        // - scheduled: training exists and is running/scheduled (in_progress)
        // - waiting: no training yet
        if ($this->hasClosedTraining()) {
            return 'completed';
        }

        if ($this->hasScheduledTraining()) {
            return 'scheduled';
        }

        return 'waiting';
    }
}
