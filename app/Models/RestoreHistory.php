<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestoreHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkpoint_id',
        'status',
        'error',
        'finished_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];
}
