<?php

namespace App\Console\Commands;

use App\Models\Database;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:prune-backups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete database backups that are older than their configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Database::query()
            ->whereNotNull('retention_days')
            ->get()
            ->each(function (Database $database) {
                $cutoff = Carbon::now()->subDays($database->retention_days);

                $database->backup_histories()
                    ->where('created_at', '<', $cutoff)
                    ->get()
                    ->each(function ($history) {
                        try {
                            @unlink($history->path.'/'.$history->filename);
                        } finally {
                            $history->delete();
                        }
                    });
            });
    }
}
