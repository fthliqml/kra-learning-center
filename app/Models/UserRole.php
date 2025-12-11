<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
    ];

    /**
     * Get the user that owns the role.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
