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
        $filename = $options['database'].'-backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.gz';

        // Define the path to store the backup
        $path = storage_path().'/databases';

        // Construct the command to perform the backup
        $command = env('MYSQL_DUMP_BINARY_PATH').'mysqldump --user='.$options['username'].
            ' --password='.$options['password'].
            ' --host='.$options['host'].
            ' '.$options['database'].
            '  | gzip > '.$path.'/'.$filename.' --no-tablespaces';

        // Execute the backup command

        exec($command);

        // Return the path and filename of the backup
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
                    ->default('3306'),
            ]);
    }
}
