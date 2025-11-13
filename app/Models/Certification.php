<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certification extends Model
{
  protected $fillable = [
    'module_id',
    'name',
    'status',
  ];

  /**
   * Get the certification module that owns the certification.
   */
  public function certificationModule(): BelongsTo
  {
    return $this->belongsTo(CertificationModule::class, 'module_id');
  }

  /**
   * Get the sessions for the certification.
   */
  public function sessions(): HasMany
  {
    return $this->hasMany(CertificationSession::class);
  }

  /**
   * Get the participants for the certification.
   */
  public function participants(): HasMany
  {
    return $this->hasMany(CertificationParticipant::class);
  }
}
