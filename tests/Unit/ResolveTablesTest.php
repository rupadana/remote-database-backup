<?php

use App\Filament\Resources\DatabaseResource\Services\Backup\Runner\MySQLBackupRunner;

/** Runner with a fake table list so no database connection is needed. */
class FakeRunner extends MySQLBackupRunner
{
    public static array $existing = [];

    public static function listTables(array $options): array
    {
        return array_combine(static::$existing, static::$existing);
    }

    public function tables(): array
    {
        return $this->resolveTables($this->getOptions());
    }
}

function resolveTables(array $existing, array $options): array
{
    FakeRunner::$existing = $existing;

    return (new FakeRunner($options))->tables();
}

it('drops selections that do not exist on the target database', function () {
    $resolved = resolveTables(['users', 'orders'], [
        'tables' => ['users', 'orders', 'pulse_aggregates', 'xdp_device_hub'],
        'structure_only_tables' => ['orders', 'pulse_aggregates'],
    ]);

    expect($resolved)->toBe(['data' => ['users'], 'structure' => ['orders']]);
});

it('falls back to a full dump when nothing selected survives', function () {
    $resolved = resolveTables(['users'], ['tables' => ['pulse_aggregates']]);

    expect($resolved)->toBe(['data' => [], 'structure' => []]);
});

it('keeps the selection when the table list cannot be read', function () {
    $resolved = resolveTables([], ['tables' => ['users', 'orders'], 'structure_only_tables' => ['orders']]);

    expect($resolved)->toBe(['data' => ['users'], 'structure' => ['orders']]);
});
