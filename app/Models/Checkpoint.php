<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'driver',
        'host',
        'database',
        'username',
        'password',
        'port',
        'cron',
        'sql_path',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function restore_histories(): HasMany
    {
        return $this->hasMany(RestoreHistory::class);
    }

    /**
     * Try to connect to the target database, throwing on failure.
     */
    public function checkConnection(): void
    {
        $dsn = $this->driver === 'pgsql'
            ? 'pgsql:host='.$this->host.';port='.($this->port ?: 5432).';dbname='.$this->database
            : 'mysql:host='.$this->host.';port='.($this->port ?: 3306).';dbname='.$this->database;

        new \PDO($dsn, $this->username, $this->password, [\PDO::ATTR_TIMEOUT => 5]);
    }
}
