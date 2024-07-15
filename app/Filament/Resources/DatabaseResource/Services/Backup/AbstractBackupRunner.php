<?php

namespace App\Filament\Resources\DatabaseResource\Services\Backup;

use Filament\Forms\Components\Builder\Block;

abstract class AbstractBackupRunner
{
    public static string $name = 'runner';

    public static string $label = 'Runner';

    public function __construct(protected string|array $options)
    {
    }

    abstract public function run(): array;

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

    abstract public static function getFilamentBlockBuilder(): Block;
}
