<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncOperation extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'local_id',
        'type',
        'result',
    ];

    protected $casts = [
        'result' => 'array',
    ];
}
