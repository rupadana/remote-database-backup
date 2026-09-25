<?php

namespace App\Jobs;

use App\Models\Checkpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class CheckpointRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public function __construct(protected int $checkpointId)
    {
    }

    public function handle(): void
    {
        $checkpoint = Checkpoint::find($this->checkpointId);

        if (! $checkpoint || ! Storage::exists($checkpoint->sql_path)) {
            return;
        }

        $history = $checkpoint->restore_histories()->create(['status' => 'running']);

        try {
            $this->restore($checkpoint);

            $history->update(['status' => 'success', 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $history->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);

            throw $e;
        }
    }

    private function restore(Checkpoint $checkpoint): void
    {
        $sqlFile = Storage::path($checkpoint->sql_path);

        $command = match ($checkpoint->driver) {
            'pgsql' => sprintf(
                'psql --username=%s --host=%s --port=%s -d %s -f %s',
                escapeshellarg($checkpoint->username),
                escapeshellarg($checkpoint->host),
                escapeshellarg($checkpoint->port ?: '5432'),
                escapeshellarg($checkpoint->database),
                escapeshellarg($sqlFile)
            ),
            default => sprintf(
                'mysql --user=%s --host=%s --port=%s %s < %s',
                escapeshellarg($checkpoint->username),
                escapeshellarg($checkpoint->host),
                escapeshellarg($checkpoint->port ?: '3306'),
                escapeshellarg($checkpoint->database),
                escapeshellarg($sqlFile)
            ),
        };

        $env = $checkpoint->driver === 'pgsql'
            ? ['PGPASSWORD' => $checkpoint->password]
            : ['MYSQL_PWD' => $checkpoint->password];

        Process::env($env)
            ->timeout(3600)
            ->run($command)
            ->throw();
    }
}
