<?php

namespace LaravelEnso\Upgrade\Services;

use LaravelEnso\Upgrade\Contracts\Applicable;
use LaravelEnso\Upgrade\Contracts\MigratesData;
use LaravelEnso\Upgrade\Contracts\MigratesPostDataMigration;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Contracts\Prioritization;
use LaravelEnso\Upgrade\Contracts\RenamesMigrations;
use LaravelEnso\Upgrade\Contracts\Upgrade;

abstract class CustomUpgrade implements Upgrade, MigratesData, Prioritization, MigratesPostDataMigration, Applicable
{
    public function __construct(protected MigratesStructure|RenamesMigrations $upgrade)
    {
    }

    public function class(): MigratesStructure|RenamesMigrations
    {
        return $this->upgrade;
    }

    public function priority(): int
    {
        return $this->upgrade instanceof Prioritization
            ? $this->upgrade->priority()
            : Prioritization::Default;
    }

    public function migratePostDataMigration(): void
    {
        if ($this->upgrade instanceof MigratesPostDataMigration) {
            $this->upgrade->migratePostDataMigration();
        }
    }

    public function applicable(): bool
    {
        return ! $this->upgrade instanceof Applicable
            || $this->upgrade->applicable();
    }
}
