<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Model code here
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'data',
        'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
