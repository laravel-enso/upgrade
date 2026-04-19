<?php

namespace LaravelEnso\TestUpgrade\Upgrades;

use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Contracts\BeforeMigration;
use LaravelEnso\Upgrade\Contracts\MigratesTable;
use LaravelEnso\Upgrade\Contracts\ShouldRunManually;

class CommandManualBeforeUpgrade implements MigratesTable, BeforeMigration, ShouldRunManually
{
    public function isMigrated(): bool
    {
        return Schema::hasTable('command_manual_before_upgrades');
    }

    public function migrateTable(): void
    {
        Schema::create('command_manual_before_upgrades', fn ($table) => $table->id());
    }
}
