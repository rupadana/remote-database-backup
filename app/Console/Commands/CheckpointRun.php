<?php

namespace App\Console\Commands;

use App\Jobs\CheckpointRestoreJob;
use App\Models\Checkpoint;
use Illuminate\Console\Command;

class CheckpointRun extends Command
{
    protected $signature = 'db:checkpoint {cron=now}';

    protected $description = 'Restore scheduled database checkpoints';

    public function handle(): void
    {
        $cron = $this->argument('cron');

        $checkpoints = Checkpoint::query();

        if ($cron !== 'now') {
            $checkpoints = $checkpoints->where('cron', $cron);
        }

        $checkpoints
            ->get()
            ->each(fn (Checkpoint $record) => CheckpointRestoreJob::dispatch($record->id));
    }
}
