<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

abstract class AbstractBackupRunner
{
    public static string $name = 'runner';

    public static string $label = 'Runner';

    public function __construct(protected string|array $options)
    {
    }

    abstract public function run(): array;

    /**
     * List table names available on the target database using the given connection options.
     */
    abstract public static function listTables(array $options): array;

    public static function getLabel(): string
    {
        return static::$label;
    }

    public function getOptions(): string|array
    {
        return $this->options;
    }

    public static function getName(): string
    {
        return static::$name;
    }

    abstract public static function getFilamentBlockComponent(): Block;

    /**
     * Resolve which tables to dump, dropping selections that no longer exist on
     * the target database (selections copied by a replicate, dropped tables, ...)
     * so the dump does not abort with "Couldn't find table".
     *
     * Both lists empty means "dump the whole database".
     *
     * @return array{data: string[], structure: string[]}
     */
    protected function resolveTables(array $options): array
    {
        $selected = array_filter($options['tables'] ?? []);
        $structureOnly = array_filter($options['structure_only_tables'] ?? []);

        // Only filter when the table list could actually be read; dumping the
        // whole database on a transient connection failure would be worse.
        if ($existing = static::listTables($options)) {
            $selected = array_intersect($selected, $existing);
            $structureOnly = array_intersect($structureOnly, $existing);
        }

        if (empty($selected)) {
            return ['data' => [], 'structure' => []];
        }

        $structureOnly = array_intersect($structureOnly, $selected);

        return [
            'data' => array_values(array_diff($selected, $structureOnly)),
            'structure' => array_values($structureOnly),
        ];
    }

    /**
     * Shared form schema letting the user, after connecting, checklist which
     * tables to include in the backup and which of those should only have
     * their structure (no data) backed up.
     */
    protected static function getTableSelectionSchema(): array
    {
        return [
            Hidden::make('tables_options')
                ->default([]),
            Actions::make([
                Actions\Action::make('loadTables')
                    ->label('Muat Daftar Tabel')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (Get $get, Set $set) {
                        $options = [
                            'host' => $get('host'),
                            'database' => $get('database'),
                            'username' => $get('username'),
                            'password' => $get('password'),
                            'port' => $get('port'),
                        ];

                        $tables = static::listTables($options);

                        $set('tables_options', $tables);

                        if (empty($tables)) {
                            Notification::make()
                                ->title('Gagal memuat daftar tabel')
                                ->body('Periksa kembali detail koneksi database.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Berhasil memuat '.count($tables).' tabel')
                            ->success()
                            ->send();
                    }),
            ]),
            CheckboxList::make('tables')
                ->label('Tabel yang di-backup')
                ->helperText('Kosongkan untuk backup seluruh tabel (struktur + data).')
                ->options(fn (Get $get) => $get('tables_options') ?? [])
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
            CheckboxList::make('structure_only_tables')
                ->label('Backup Struktur Saja (tanpa data)')
                ->helperText('Tabel yang dicentang di sini hanya akan di-backup strukturnya. Tabel lain yang dipilih di atas akan di-backup struktur + data.')
                ->options(fn (Get $get) => $get('tables_options') ?? [])
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ];
    }
}
