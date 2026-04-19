<?php

namespace LaravelEnso\TestUpgrade\Upgrades;

use LaravelEnso\Upgrade\Contracts\BeforeMigration;
use LaravelEnso\Upgrade\Contracts\Prioritization;
use LaravelEnso\Upgrade\Contracts\ShouldRunManually;
use LaravelEnso\Upgrade\Contracts\Upgrade;

class ManualBeforeRanUpgrade implements Upgrade, BeforeMigration, ShouldRunManually, Prioritization
{
    public function isMigrated(): bool
    {
        return true;
    }

    public function priority(): int
    {
        return 5;
    }
}
