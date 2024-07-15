<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'filename',
        'is_pruned',
        'database_id',
    ];

    protected $casts = [
        'is_pruned' => 'boolean',
    ];
}
