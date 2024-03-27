<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('db:backup "0 0 * * *"')->daily()->name('backup: daily');
        $schedule->command('db:backup "0 * * * *"')->hourly()->name('backup: hourly');
        $schedule->command('db:backup "* * * * *"')->everyMinute()->name('backup: every minute');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
