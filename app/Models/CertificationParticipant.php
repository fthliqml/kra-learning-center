<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificationParticipant extends Model
{
  protected $fillable = [
    'certification_id',
    'employee_id',
    'assigned_at',
  ];

  protected $casts = [
    'assigned_at' => 'datetime',
  ];

  /**
   * Get the certification that owns the participant.
   */
  public function certification(): BelongsTo
  {
    return $this->belongsTo(Certification::class);
  }

  /**
   * Get the attendances for the participant.
   */
  public function attendances(): HasMany
  {
    return $this->hasMany(CertificationAttendance::class, 'participant_id');
  }

  /**
   * Get the scores for the participant.
   */
  public function scores(): HasMany
  {
    return $this->hasMany(CertificationScore::class, 'participant_id');
  }
}
