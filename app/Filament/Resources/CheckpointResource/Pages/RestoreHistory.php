<?php

namespace App\Filament\Resources\CheckpointResource\Pages;

use App\Filament\Resources\CheckpointResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class RestoreHistory extends ManageRelatedRecords
{
    protected static string $resource = CheckpointResource::class;

    protected static string $relationship = 'restore_histories';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function getNavigationLabel(): string
    {
        return 'Restore Histories';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('error')
                    ->limit(60)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started At'),
                Tables\Columns\TextColumn::make('finished_at'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
