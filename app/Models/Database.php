<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Database extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cron',
        'retention_days',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'retention_days' => 'integer',
    ];

    public function backup_histories(): HasMany
    {
        return $this->hasMany(BackupHistory::class, 'database_id');
    }
}
