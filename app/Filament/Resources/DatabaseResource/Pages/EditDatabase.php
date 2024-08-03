<?php

namespace App\Filament\Resources\DatabaseResource\Pages;

use App\Filament\Resources\DatabaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDatabase extends EditRecord
{
    protected static string $resource = DatabaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DatabaseResource\Actions\DatabaseBackupPageAction::make(),
            DatabaseResource\Actions\DatabaseTest::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
