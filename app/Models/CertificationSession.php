<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificationSession extends Model
{
  protected $fillable = [
    'certification_id',
    'type',
    'date',
    'start_time',
    'end_time',
    'location',
  ];

  protected $casts = [
    'date' => 'date',
  ];

  /**
   * Get the certification that owns the session.
   */
  public function certification(): BelongsTo
  {
    return $this->belongsTo(Certification::class);
  }

  /**
   * Get the attendances for the session.
   */
  public function attendances(): HasMany
  {
    return $this->hasMany(CertificationAttendance::class, 'session_id');
  }

  /**
   * Get the scores for the session.
   */
  public function scores(): HasMany
  {
    return $this->hasMany(CertificationScore::class, 'session_id');
  }
}
