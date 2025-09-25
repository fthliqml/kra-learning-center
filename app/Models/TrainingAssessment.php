<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TrainingAssessment extends Model
{
    protected $table = 'training_assessments';

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
}
