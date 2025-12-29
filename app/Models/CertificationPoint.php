<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationPoint extends Model
{
  protected $fillable = [
    'employee_id',
    'total_points',
  ];

  protected $casts = [
    'total_points' => 'integer',
  ];

  /**
   * Get the user/employee associated with this certification point record.
   * Note: This relation may be updated to use external API in the future.
   */
  public function employee(): BelongsTo
  {
    return $this->belongsTo(User::class, 'employee_id');
  }

  /**
   * Add points to the total.
   */
  public function addPoints(int $points): self
  {
    $this->total_points += $points;
    $this->save();

    return $this;
  }

  /**
   * Get or create certification point record for an employee.
   */
  public static function getOrCreateForEmployee(int $employeeId): self
  {
    return self::firstOrCreate(
      ['employee_id' => $employeeId],
      ['total_points' => 0]
    );
  }
}
