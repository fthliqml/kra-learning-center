<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationAttendance extends Model
{
  const UPDATED_AT = 'updated_at';
  const CREATED_AT = null;

  protected $fillable = [
    'participant_id',
    'session_id',
    'status',
    'absence_notes',
    'recorded_at',
  ];

  protected $casts = [
    'recorded_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the participant that owns the attendance.
   */
  public function participant(): BelongsTo
  {
    return $this->belongsTo(CertificationParticipant::class, 'participant_id');
  }

  /**
   * Get the session that owns the attendance.
   */
  public function session(): BelongsTo
  {
    return $this->belongsTo(CertificationSession::class, 'session_id');
  }
}
