<?php

namespace LaravelEnso\Upgrade\Helpers;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Services\DBAL\Connection;

class Table
{
    public static function exists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    public static function hasIndex(string $table, string $index): bool
    {
        $currentIndexes = App::make(Connection::class)
            ->schemaManager()
            ->listTableIndexes($table);

        return Collection::wrap($currentIndexes)->has($index);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    public static function hasForeignKey(string $table, string $name): bool
    {
        return App::make(Connection::class)
            ->introspectTable($table)
            ->hasForeignKey($name);
    }

    public static function foreignKey(string $table, string $name): ?ForeignKeyConstraint
    {
        return App::make(Connection::class)
            ->introspectTable($table)
            ->getForeignKey($name);
    }

    public static function hasType(string $table, string $column, string $type): bool
    {
        $field = Collection::wrap(DB::select("SHOW FIELDS FROM {$table}"))
            ->first(fn ($col) => $col->Field === $column);

        return $field
            ? $field->Type === $type
            : false;
    }
}
