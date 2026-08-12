<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup\Runner;

use App\Filament\Resources\DatabaseResource\Services\Backup\AbstractBackupRunner;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;

class MySQLBackupRunner extends AbstractBackupRunner
{
    public static string $name = 'mysql';

    public static string $label = 'MySQL';

    /**
     * Run the database backup process.
     *
     * @throws Exception
     */
    public function run(): array
    {
        // Get the options for the backup
        $options = $this->getOptions();

        // Check if options is an array
        if (! is_array($options)) {
            throw new Exception('String options are not supported');
        }

        // Generate the filename for the backup
        $filename = $options['database'].'-backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.sql.gz';

        // Define the path to store the backup
        $path = storage_path().'/databases';

        $sqlFile = $path.'/'.substr($filename, 0, -3);

        // Base command shared by all dumps
        $baseCommand = 'mariadb-dump --user='.escapeshellarg($options['username']).
            ' --password='.escapeshellarg($options['password']).
            ' --host='.escapeshellarg($options['host']).
            ' --skip-ssl';

        ['data' => $dataTables, 'structure' => $structureOnlyTables] = $this->resolveTables($options);

        if (empty($dataTables) && empty($structureOnlyTables)) {
            // No selection made: back up the whole database (structure + data)
            exec($baseCommand.' '.escapeshellarg($options['database']).' > '.escapeshellarg($sqlFile));
        } else {
            if (! empty($structureOnlyTables)) {
                $tables = implode(' ', array_map('escapeshellarg', $structureOnlyTables));
                exec($baseCommand.' --no-data '.escapeshellarg($options['database']).' '.$tables.' > '.escapeshellarg($sqlFile));
            }

            if (! empty($dataTables)) {
                $tables = implode(' ', array_map('escapeshellarg', $dataTables));
                $redirect = file_exists($sqlFile) ? '>>' : '>';
                exec($baseCommand.' '.escapeshellarg($options['database']).' '.$tables.' '.$redirect.' '.escapeshellarg($sqlFile));
            }
        }

        // Compress the dump
        exec('gzip -f '.escapeshellarg($sqlFile));

        // Return the path and filename of the backup
        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    public static function listTables(array $options): array
    {
        try {
            $pdo = new \PDO(
                'mysql:host='.$options['host'].';port='.($options['port'] ?: 3306).';dbname='.$options['database'],
                $options['username'],
                $options['password'],
                [\PDO::ATTR_TIMEOUT => 5]
            );

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

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
                    ->default('3306'),
                ...static::getTableSelectionSchema(),
            ]);
    }
}
