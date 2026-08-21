<?php

use App\Filament\Resources\DatabaseResource\Services\Backup\Runner\MySQLBackupRunner;
use App\Filament\Resources\DatabaseResource\Services\Backup\Runner\PostgreSQLBackupRunner;
use Illuminate\Support\Facades\Process;

/** Runners with a stubbed table list so no real connection is attempted. */
class StubMySQLRunner extends MySQLBackupRunner
{
    public static array $existing = [];

    public static function listTables(array $options): array
    {
        return array_combine(static::$existing, static::$existing);
    }
}

class StubPostgresRunner extends PostgreSQLBackupRunner
{
    public static function listTables(array $options): array
    {
        return [];
    }
}

function mysqlOptions(array $overrides = []): array
{
    return array_merge([
        'host' => '10.0.0.1',
        'port' => '3307',
        'database' => 'jinomnet_panel',
        'username' => 'root',
        'password' => 's3cr3t p@ss',
    ], $overrides);
}

beforeEach(fn () => StubMySQLRunner::$existing = []);

it('dumps mysql with --hex-blob so the file stays importable', function () {
    Process::fake();

    $result = (new StubMySQLRunner(mysqlOptions()))->run();

    expect($result['filename'])->toStartWith('jinomnet_panel-backup-')->toEndWith('.sql.gz');

    Process::assertRan(function ($process) {
        if (! str_contains($process->command, 'mariadb-dump')) {
            return false;
        }

        expect($process->command)
            ->toContain('--hex-blob')      // raw binary breaks UTF-8 on import
            ->toContain("--port='3307'")   // the port field used to be ignored
            ->not->toContain('s3cr3t');    // password never lands in argv

        expect($process->environment)->toMatchArray(['MYSQL_PWD' => 's3cr3t p@ss']);

        return true;
    });

    Process::assertRan(fn ($process) => str_contains($process->command, 'gzip -f'));
});

it('keeps --hex-blob on the per-table dumps too', function () {
    Process::fake();
    StubMySQLRunner::$existing = ['users', 'sessions'];

    (new StubMySQLRunner(mysqlOptions([
        'tables' => ['users', 'sessions'],
        'structure_only_tables' => ['sessions'],
    ])))->run();

    $dumps = [];

    Process::assertRanTimes(function ($process) use (&$dumps) {
        if (! str_contains($process->command, 'mariadb-dump')) {
            return false;
        }

        $dumps[] = $process->command;

        return true;
    }, 2);

    expect($dumps)->each->toContain('--hex-blob');
    expect($dumps[0])->toContain('--no-data');   // structure-only pass runs first
});

it('fails loudly instead of recording a broken backup', function () {
    Process::fake(['*' => Process::result(output: '', errorOutput: 'boom', exitCode: 1)]);

    expect(fn () => (new StubMySQLRunner(mysqlOptions()))->run())
        ->toThrow(Illuminate\Process\Exceptions\ProcessFailedException::class);
});

it('passes the postgres password through the environment', function () {
    Process::fake();

    (new StubPostgresRunner([
        'host' => '10.0.0.2',
        'port' => '',
        'database' => 'shop',
        'username' => 'postgres',
        'password' => 'pgp@ss',
    ]))->run();

    Process::assertRan(function ($process) {
        if (! str_contains($process->command, 'pg_dump')) {
            return false;
        }

        expect($process->command)
            ->toContain("--port='5432'")   // blank port used to produce --port=''
            ->not->toContain('pgp@ss');

        expect($process->environment)->toMatchArray(['PGPASSWORD' => 'pgp@ss']);

        return true;
    });
});
