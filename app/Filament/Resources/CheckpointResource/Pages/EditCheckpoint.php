<?php

namespace App\Filament\Resources\CheckpointResource\Pages;

use App\Filament\Resources\CheckpointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCheckpoint extends EditRecord
{
    protected static string $resource = CheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CheckpointResource\Actions\CheckpointCheckConnectionPageAction::make(),
            CheckpointResource\Actions\CheckpointRunPageAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
