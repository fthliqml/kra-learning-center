<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorDailyRecord extends Model
{
  protected $fillable = [
    'instructor_id',
    'date',
    'code',
    'group',
    'activity',
    'remarks',
    'hour',
  ];

  protected $casts = [
    'date' => 'date',
    'hour' => 'decimal:1',
  ];

  /**
   * Boot method to auto-generate code
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($record) {
      if (empty($record->code)) {
        $record->code = self::generateCode();
      }
    });
  }

  /**
   * Generate unique code: IDR-00001
   */
  public static function generateCode(): string
  {
    $lastRecord = self::orderBy('id', 'desc')->first();
    $nextId = $lastRecord ? $lastRecord->id + 1 : 1;
    return 'IDR-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
  }

  /**
   * Get the instructor (user) that owns the record
   */
  public function instructor(): BelongsTo
  {
    return $this->belongsTo(User::class, 'instructor_id');
  }

  /**
   * Scope to filter by instructor
   */
  public function scopeForInstructor($query, $userId)
  {
    return $query->where('instructor_id', $userId);
  }

  /**
   * Get available group options
   */
  public static function getGroupOptions(): array
  {
    return [
      ['value' => 'JAI', 'label' => 'JAI'],
      ['value' => 'JAO', 'label' => 'JAO'],
    ];
  }
}
