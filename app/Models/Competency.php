<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    protected $table = 'competency';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
    ];

    /**
     * Generate the next code for a given type.
     */
    public static function generateCode(string $type): string
    {
        $lastCompetency = self::where('type', $type)
            ->orderByRaw("CAST(SUBSTRING(code, " . (strlen($type) + 1) . ") AS UNSIGNED) DESC")
            ->first();

        if ($lastCompetency) {
            $lastNumber = (int) substr($lastCompetency->code, strlen($type));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $type . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the matrix entries (employees trained) for this competency.
     */
    public function matrixEntries(): HasMany
    {
        return $this->hasMany(CompetencyMatrix::class, 'competency_id');
    }
}
