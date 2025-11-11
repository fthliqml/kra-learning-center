<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for training_requests table.
 * NOTE: Model name 'Request' may collide with Illuminate\Http\Request; alias HTTP Request as needed in consumers.
 *
 * @property int $id
 * @property int $created_by
 * @property int $user_id
 * @property string $competency
 * @property string $reason
 * @property string $status     pending|approved|rejected
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Request extends Model
{
    // Explicit table to avoid default 'requests'
    protected $table = 'training_requests';

    /**
     * Centralized status constants.
     */
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'created_by',
        'user_id',
        'competency',
        'reason',
        'status',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'created_by' => 'integer',
        'user_id'    => 'integer',
        'competency' => 'string',
        'reason'     => 'string',
        'status'     => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attributes mirroring DB defaults.
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    /* ========================= Relationships ========================= */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The target user for whom the training is requested.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ========================= Helpers ========================= */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /* ========================= Mutators ========================= */

    /**
     * Normalize and validate status assignment against enum values.
     */
    public function setStatusAttribute($value): void
    {
        $val = is_string($value) ? strtolower(trim($value)) : null;
        $this->attributes['status'] = in_array($val, self::STATUSES, true)
            ? $val
            : self::STATUS_PENDING;
    }
}
