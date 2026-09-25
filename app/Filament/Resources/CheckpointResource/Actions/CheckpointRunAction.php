<?php

namespace App\Filament\Resources\CheckpointResource\Actions;

use App\Jobs\CheckpointRestoreJob;
use App\Models\Checkpoint;
use Filament\Tables\Actions\Action;

class CheckpointRunAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('run-checkpoint');

        $this->action(function (Checkpoint $record) {
            CheckpointRestoreJob::dispatch($record->id);
            $this->sendSuccessNotification();
        });

        $this->label('Restore to Checkpoint');

        $this->icon('heroicon-o-arrow-uturn-left');

        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalDescription('Database tujuan akan ditimpa dengan isi file SQL checkpoint ini. Tindakan ini tidak bisa dibatalkan.');

        $this->successNotificationTitle('Restore checkpoint dijadwalkan');
    }
}
