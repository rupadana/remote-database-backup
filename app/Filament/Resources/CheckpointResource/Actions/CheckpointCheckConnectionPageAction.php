<?php

namespace App\Filament\Resources\CheckpointResource\Actions;

use App\Models\Checkpoint;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CheckpointCheckConnectionPageAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('check-connection');

        $this->action(function (Checkpoint $record) {
            try {
                $record->checkConnection();

                Notification::make()
                    ->title('Koneksi berhasil')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Koneksi gagal')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        });

        $this->label('Check Connection');

        $this->icon('heroicon-o-signal');

        $this->color('gray');
    }
}
