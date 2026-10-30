<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Process;

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
     * Run a dump/compress command, failing loudly.
     *
     * exec() ignores the exit status, so a dump that died halfway used to be
     * recorded as a perfectly good backup. Passwords go through $env so they
     * never show up in `ps`.
     *
     * @param  array<string, string>  $env
     */
    protected function shell(string $command, array $env = []): void
    {
        Process::env($env)
            ->timeout(3600) // Laravel defaults to 60s; a real dump takes longer
            ->run($command)
            ->throw();
    }

    /**
     * Tables whose data is skipped (structure only). Everything else, including
     * tables created after this database was configured, is dumped in full.
     *
     * @return string[]
     */
    protected function structureOnlyTables(array $options): array
    {
        return array_values(array_filter($options['structure_only_tables'] ?? []));
    }

    /**
     * Shared form schema letting the user, after connecting, checklist which
     * tables should only have their structure (no data) backed up.
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
            CheckboxList::make('structure_only_tables')
                ->label('Backup Struktur Saja (tanpa data)')
                ->helperText('Semua tabel (termasuk tabel baru) di-backup struktur + data, kecuali tabel yang dicentang di sini: hanya strukturnya.')
                ->options(fn (Get $get) => $get('tables_options') ?? [])
                ->columns(2)
                ->bulkToggleable()
                ->searchable(),
        ];
    }
}
