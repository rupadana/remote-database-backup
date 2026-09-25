<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CheckpointResource\Actions\CheckpointCheckConnectionAction;
use App\Filament\Resources\CheckpointResource\Actions\CheckpointRunAction;
use App\Filament\Resources\CheckpointResource\Pages;
use App\Models\Checkpoint;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CheckpointResource extends Resource
{
    protected static ?string $model = Checkpoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                Select::make('driver')
                    ->options([
                        'mysql' => 'MySQL',
                        'pgsql' => 'PostgreSQL',
                    ])
                    ->default('mysql')
                    ->required(),
                TextInput::make('host')
                    ->required(),
                TextInput::make('database')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context) => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state)),
                TextInput::make('port'),
                Actions::make([
                    Actions\Action::make('checkConnection')
                        ->label('Check Connection')
                        ->icon('heroicon-o-signal')
                        ->color('gray')
                        ->action(function (Get $get) {
                            $driver = $get('driver');
                            $dsn = $driver === 'pgsql'
                                ? 'pgsql:host='.$get('host').';port='.($get('port') ?: 5432).';dbname='.$get('database')
                                : 'mysql:host='.$get('host').';port='.($get('port') ?: 3306).';dbname='.$get('database');

                            try {
                                new \PDO($dsn, $get('username'), $get('password'), [\PDO::ATTR_TIMEOUT => 5]);

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
                        }),
                ]),
                Select::make('cron')
                    ->label('Jadwal')
                    ->options([
                        '* * * * *' => 'Every Minute',
                        '0 * * * *' => 'Every Hour',
                        '0 0 * * *' => 'Every 00:00',
                    ])
                    ->required(),
                FileUpload::make('sql_path')
                    ->label('Checkpoint SQL File')
                    ->disk('local')
                    ->directory('checkpoints')
                    ->preserveFilenames()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver'),
                Tables\Columns\TextColumn::make('database'),
                Tables\Columns\TextColumn::make('cron')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                CheckpointCheckConnectionAction::make(),
                CheckpointRunAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCheckpoints::route('/'),
            'create' => Pages\CreateCheckpoint::route('/create'),
            'edit' => Pages\EditCheckpoint::route('/{record}/edit'),
            'restore-histories' => Pages\RestoreHistory::route('/{record}/restore-histories'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditCheckpoint::class,
            Pages\RestoreHistory::class,
        ]);
    }
}
