<?php

namespace App\Filament\Resources\DatabaseResource\Actions;

use App\Jobs\DatabaseBackupJob;
use App\Models\Database;
use Filament\Tables\Actions\Action;

class DatabaseBackupAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('backup-database');

        $this->action(function (Database $record) {
            DatabaseBackupJob::dispatchSync($record->id);
            $this->sendSuccessNotification();
        });

        $this->label('Backup Now');

        $this->color('success');

        $this->successNotificationTitle('Successfully Backed Up');
    }
}
