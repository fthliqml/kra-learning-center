<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon; // for phpdoc only

class TestAttempt extends Model
{
    // Updated to plural table name after migration change
    protected $table = 'test_attempts';

    /**
     * Centralized status constants to avoid magic strings.
     */
    public const STATUS_STARTED      = 'started';
    public const STATUS_SUBMITTED    = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_EXPIRED      = 'expired';

    public const STATUSES = [
        self::STATUS_STARTED,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_EXPIRED,
    ];

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'test_id',
        'attempt_number',
        'status',
        'auto_score',
        'manual_score',
        'total_score',
        'is_passed',
        'started_at',
        'submitted_at',
        'expired_at',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'attempt_number' => 'integer',
        'auto_score'     => 'integer',
        'manual_score'   => 'integer',
        'total_score'    => 'integer',
        'is_passed'      => 'boolean',
        'started_at'     => 'datetime',
        'submitted_at'   => 'datetime',
        'expired_at'     => 'datetime',
    ];

    /* ========================= Relationships ========================= */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    // Placeholder relation; will be created after TestAttemptAnswer model exists
    public function answers(): HasMany
    {
        return $this->hasMany(TestAttemptAnswer::class, 'attempt_id');
    }

    /* ========================= Helpers / Domain Logic ========================= */

    /**
     * Recalculate and update total_score & is_passed (in-memory only unless saved).
     * Optionally pass a passing score to evaluate pass/fail.
     */
    public function recalc(?int $passingScore = null): static
    {
        $this->total_score = (int) ($this->auto_score + $this->manual_score);
        if ($passingScore !== null) {
            $this->is_passed = $this->total_score >= $passingScore;
        }
        return $this;
    }

    /** Convenience checks */
    public function isStarted(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }
    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }
}
