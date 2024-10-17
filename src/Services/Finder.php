<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Contracts\RenamesMigrations;

class Finder
{
    public function upgrades(): Collection
    {
        return $this->upgradePackages()
            ->map(fn ($folder) => $this->upgradeClasses($folder))
            ->flatten();
    }

    private function upgradePackages(): Collection
    {
        return Collection::wrap(Config::get('enso.upgrade.vendors'))
            ->map(fn ($vendor) => base_path("vendor/{$vendor}"))
            ->map(fn ($vendor) => File::directories($vendor))
            ->flatten()
            ->concat(Collection::wrap(Config::get('enso.upgrade.folders'))
                ->map(fn ($folder) => base_path($folder)))
            ->map(fn ($path) => new Package($path))
            ->filter->qualifies();
    }

    private function upgradeClasses(Package $package): Collection
    {
        return $package->upgradeClasses()->map(fn ($class) => $this->upgrade($class));
    }

    private function upgrade(string $class)
    {
        $upgrade = new $class();

        if ($upgrade instanceof MigratesStructure) {
            return new Structure($upgrade);
        } elseif ($upgrade instanceof RenamesMigrations) {
            return new Migrations($upgrade);
        }

        return $upgrade;
    }
}
