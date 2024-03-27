<?php

namespace App\Console\Commands;

use App\Jobs\DatabaseBackupJob;
use App\Models\Database;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {cron=now}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automating Daily Backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cron = $this->argument('cron');

        $databases = Database::query();

        if($cron !== 'now') {
            $databases = $databases->where('cron', $cron);
        }

        $databases = $databases->get();

        $databases
            ->each(function (Database $record) {
                DatabaseBackupJob::dispatch($record->id);
            });
    }
}
