<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup\Runner;

use App\Filament\Resources\DatabaseResource\Services\Backup\AbstractBackupRunner;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\File;

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

        // A dump left behind by a previous failed run would otherwise be appended to
        File::delete($sqlFile);

        // Base command shared by all dumps
        $baseCommand = 'mariadb-dump'.
            // Without this, binary/blob columns are written as raw bytes, the dump
            // stops being valid UTF-8, and importing it fails with an encoding error.
            ' --hex-blob'.
            ' --user='.escapeshellarg($options['username']).
            ' --host='.escapeshellarg($options['host']).
            ' --port='.escapeshellarg($options['port'] ?: '3306').
            ' --skip-ssl';

        $env = ['MYSQL_PWD' => $options['password']];

        ['data' => $dataTables, 'structure' => $structureOnlyTables] = $this->resolveTables($options);

        if (empty($dataTables) && empty($structureOnlyTables)) {
            // No selection made: back up the whole database (structure + data)
            $this->shell($baseCommand.' '.escapeshellarg($options['database']).' > '.escapeshellarg($sqlFile), $env);
        } else {
            if (! empty($structureOnlyTables)) {
                $tables = implode(' ', array_map('escapeshellarg', $structureOnlyTables));
                $this->shell($baseCommand.' --no-data '.escapeshellarg($options['database']).' '.$tables.' > '.escapeshellarg($sqlFile), $env);
            }

            if (! empty($dataTables)) {
                $tables = implode(' ', array_map('escapeshellarg', $dataTables));
                $redirect = file_exists($sqlFile) ? '>>' : '>';
                $this->shell($baseCommand.' '.escapeshellarg($options['database']).' '.$tables.' '.$redirect.' '.escapeshellarg($sqlFile), $env);
            }
        }

        // Compress the dump
        $this->shell('gzip -f '.escapeshellarg($sqlFile));

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
