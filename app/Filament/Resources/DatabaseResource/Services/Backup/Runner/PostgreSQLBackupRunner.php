<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup\Runner;

use App\Filament\Resources\DatabaseResource\Services\Backup\AbstractBackupRunner;
use Carbon\Carbon;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;

class PostgreSQLBackupRunner extends AbstractBackupRunner
{
    public static string $name = 'postgresql';

    public static string $label = 'PostgreSQL';

    public function run(): array
    {
        $options = $this->getOptions();

        if (! is_array($options)) {
            throw new \Exception('String options are not supported');
        }

        $filename = $options['database'].'-backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.gz';

        $path = storage_path().'/databases';

        putenv('PGPASSWORD='.$options['password']);

        $command = 'PGPASSWORD='.$options['password'].
            ' pg_dump --username='.$options['username'].
            ' --port='.$options['port'].
            ' --host='.$options['host'].
            ' -d '.$options['database'].
            '  | gzip > '.$path.'/'.$filename;

        exec($command);

        putenv('PGPASSWORD=');

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    public static function getFilamentBlockComponent(): Block
    {
        return Block::make(static::getName())
            ->label(static::getLabel())
            ->schema([
                TextInput::make('host')
                    ->ip()
                    ->required(),
                TextInput::make('database')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable(),
                TextInput::make('port')
                    ->default('5432'),
            ]);
    }
}
