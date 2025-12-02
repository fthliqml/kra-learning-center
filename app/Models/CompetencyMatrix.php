<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyMatrix extends Model
{
    use HasFactory;

    protected $table = 'competency_matrixs';

    protected $fillable = [
        'competency_id',
        'employees_trained_id',
    ];

    /**
     * Get the competency associated with this matrix.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /**
     * Get the employee that was trained.
     */
    public function employeeTrained(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employees_trained_id');
    }
}
