<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSurvey extends Model
{
    // Table: training_surveys
    protected $table = 'training_surveys';

    // Mass assignable attributes
    protected $fillable = [
        'training_id',
        'template_id',
        'level',
        'status',
    ];

    // Attribute casts
    protected $casts = [
        'training_id' => 'integer',
        'template_id' => 'integer',
        'level' => 'integer',
    ];

    // Status Constant
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_DRAFT,
        self::STATUS_INCOMPLETE,
    ];

    /**
     * Summary of training
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Training, TrainingSurvey>
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Summary of template
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SurveyTemplate, TrainingSurvey>
     */
    public function template()
    {
        return $this->belongsTo(SurveyTemplate::class, 'template_id');
    }

    /**
     * Summary of scopeStatus
     * @param mixed $query
     * @param string $status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: surveys visible to an employee (only surveys whose training has an assessment for the employee)
     *
     * @param mixed $query
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->whereHas('training.assessments', function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId);
        });
    }

    /**
     * Convenience: get surveys for an employee by id.
     */
    public static function forEmployeeId(int $employeeId)
    {
        return static::query()->forEmployee($employeeId);
    }
}
