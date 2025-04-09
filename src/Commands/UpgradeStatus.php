<?php

namespace LaravelEnso\Upgrade\Commands;

use Illuminate\Console\Command;
use LaravelEnso\Upgrade\Enums\TableHeader;
use LaravelEnso\Upgrade\Services\UpgradeStatus as Service;

class UpgradeStatus extends Command
{
    protected $signature = 'enso:upgrade:status';

    protected $description = "This command will display the existing upgrade's status";

    public function handle()
    {
        $this->table(TableHeader::mappings(), (new Service())->handle());
    }
}
