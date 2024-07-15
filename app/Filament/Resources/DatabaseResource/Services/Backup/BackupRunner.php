<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup;

class BackupRunner
{
    public static array $runner = [];

    public static function of(string $name, string|array $options): AbstractBackupRunner
    {
        if (! isset(static::$runner[$name])) {
            throw new \Exception("Backup runner {$name} does not exist");
        }

        return new (static::$runner[$name])($options);
    }

    public static function all(): array
    {
        return static::$runner;
    }

    public static function register(string $runner): static
    {
        if (! class_exists($runner)) {
            throw new \Exception("Backup runner {$runner} does not exist");
        }

        if (! is_subclass_of($runner, AbstractBackupRunner::class)) {
            throw new \Exception("Backup runner {$runner} is not an instance of AbstractBackupRunner");
        }

        static::$runner[$runner::getName()] = $runner;

        return new static;
    }
}
