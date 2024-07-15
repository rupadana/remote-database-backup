<?php

namespace App\Providers;

use App\Filament\Resources\DatabaseResource\Services\Backup\BackupRunner;
use App\Filament\Resources\DatabaseResource\Services\Backup\Runner\MySQLBackupRunner;
use App\Filament\Resources\DatabaseResource\Services\Backup\Runner\PostgreSQLBackupRunner;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BackupRunner::class, fn () => new BackupRunner());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BackupRunner::register(MySQLBackupRunner::class);
        BackupRunner::register(PostgreSQLBackupRunner::class);
    }
}
