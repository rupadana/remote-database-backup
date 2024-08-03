<?php

namespace App\Jobs;

use App\Filament\Resources\DatabaseResource\Services\Backup\BackupRunner;
use App\Models\Database;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $databaseId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = Database::find($this->databaseId);

        if ($record && $record->data !== null) {
            $data = $record->data[0];
            $runner = BackupRunner::of($data['type'], $data['data'])->run();

            if ($runner) {
                $filesize = filesize($runner['path'] . '/'. $runner['filename']);

                $record->backup_histories()->create([
                    'path' => $runner['path'],
                    'filename' => $runner['filename'],
                    'filesize' => $filesize,
                ]);
            }
        }
    }
}
