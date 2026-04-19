<?php

namespace LaravelEnso\TestUpgrade\Upgrades;

use LaravelEnso\Upgrade\Contracts\Applicable;
use LaravelEnso\Upgrade\Contracts\Prioritization;
use LaravelEnso\Upgrade\Contracts\Upgrade;

class InapplicableStatusUpgrade implements Upgrade, Applicable, Prioritization
{
    public function isMigrated(): bool
    {
        return false;
    }

    public function applicable(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 20;
    }
}
