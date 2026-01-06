<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificationModule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'certification_modules';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'module_title',
        'level',
        'group_certification',
        'points_per_module',
        'new_gex',
        'duration',
        'major_component',
        'mach_model',
        'theory_passing_score',
        'practical_passing_score',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points_per_module' => 'integer',
        'new_gex' => 'float',
        'duration' => 'integer',
        'theory_passing_score' => 'float',
        'practical_passing_score' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active certification modules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the certifications for the certification module.
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class, 'module_id');
    }
}
