<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class TrainingSession extends Model
{
    protected $table = 'training_sessions';

    protected $fillable = [
        'training_id',
        'trainer_id',
        'room_name',
        'room_location',
        'start_time',
        'date',
        'end_time',
        'day_number',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function attendances()
    {
        return $this->hasMany(TrainingAttendance::class, 'session_id');
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    /**
     * Normalized 'Y-m-d' date string for easy comparisons.
     */
    protected function isoDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->date)
                    return null;
                if ($this->date instanceof Carbon)
                    return $this->date->format('Y-m-d');
                try {
                    return Carbon::parse($this->date)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        );
    }

    /**
     * Trainer display name: prefer trainer.name, fallback to trainer.user.name
     */
    protected function trainerDisplayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $t = $this->trainer;
                if (!$t)
                    return null;
                return $t->name ?? ($t->user->name ?? null);
            }
        );
    }

    /**
     * Two-letter initials from trainerDisplayName
     */
    protected function trainerInitials(): Attribute
    {
        return Attribute::make(
            get: function () {
                $name = $this->trainer_display_name;
                if (!$name)
                    return null;
                $parts = preg_split('/\s+/', trim($name));
                $initials = '';
                foreach ($parts as $p) {
                    $initials .= mb_substr($p, 0, 1);
                    if (mb_strlen($initials) >= 2)
                        break;
                }
                return mb_strtoupper($initials);
            }
        );
    }
}
