<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TrainingAssesment extends Model
{
    protected $table = 'training_assesments';

    protected $fillable = [
        'training_id',
        'employee_id',
        'pretest_score',
        'posttest_score',
        'practical_score',
        'status',
    ];

    protected $casts = [
        'pretest_score' => 'float',
        'posttest_score' => 'float',
        'practical_score' => 'float',
    ];

    /**
     * Get the training for the assessment.
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    /**
     * Get the employee for the assessment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Calculate the average score.
     */
    public function averageScore(): Attribute
    {
        return Attribute::make(
            get: function () {
                $scores = array_filter([
                    $this->pretest_score,
                    $this->posttest_score,
                    $this->practical_score
                ]);

                return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            }
        );
    }

    /**
     * Scope to get assessments by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get assessments for a specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get passed assessments.
     */
    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    /**
     * Scope to get failed assessments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
