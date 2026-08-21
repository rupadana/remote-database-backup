<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup\Runner;

use App\Filament\Resources\DatabaseResource\Services\Backup\AbstractBackupRunner;
use Carbon\Carbon;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\File;

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

        $filename = $options['database'].'-backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.sql.gz';

        $path = storage_path().'/databases';

        $sqlFile = $path.'/'.substr($filename, 0, -3);

        // A dump left behind by a previous failed run would otherwise be appended to
        File::delete($sqlFile);

        $baseCommand = 'pg_dump --username='.escapeshellarg($options['username']).
            ' --port='.escapeshellarg($options['port'] ?: '5432').
            ' --host='.escapeshellarg($options['host']).
            ' -d '.escapeshellarg($options['database']);

        // Per-process env, so it cannot leak into other jobs the way putenv() did
        $env = ['PGPASSWORD' => $options['password']];

        ['data' => $dataTables, 'structure' => $structureOnlyTables] = $this->resolveTables($options);

        if (empty($dataTables) && empty($structureOnlyTables)) {
            // No selection made: back up the whole database (structure + data)
            $this->shell($baseCommand.' > '.escapeshellarg($sqlFile), $env);
        } else {
            if (! empty($structureOnlyTables)) {
                $tables = implode(' ', array_map(fn ($table) => '-t '.escapeshellarg($table), $structureOnlyTables));
                $this->shell($baseCommand.' --schema-only '.$tables.' > '.escapeshellarg($sqlFile), $env);
            }

            if (! empty($dataTables)) {
                $tables = implode(' ', array_map(fn ($table) => '-t '.escapeshellarg($table), $dataTables));
                $redirect = file_exists($sqlFile) ? '>>' : '>';
                $this->shell($baseCommand.' '.$tables.' '.$redirect.' '.escapeshellarg($sqlFile), $env);
            }
        }

        $this->shell('gzip -f '.escapeshellarg($sqlFile));

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    public static function listTables(array $options): array
    {
        try {
            $pdo = new \PDO(
                'pgsql:host='.$options['host'].';port='.($options['port'] ?: 5432).';dbname='.$options['database'],
                $options['username'],
                $options['password'],
                [\PDO::ATTR_TIMEOUT => 5]
            );

            $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            return array_combine($tables, $tables);
        } catch (\Throwable $e) {
            return [];
        }
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
                ...static::getTableSelectionSchema(),
            ]);
    }
}
