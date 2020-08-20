<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Contracts\Upgrade;
use ReflectionClass;

class Finder
{
    public static array $folders = ['.'];
    public static array $vendors = ['laravel-enso'];

    public function upgrades(): Collection
    {
        return $this->initUpgrades()
            ->filter->hasUpgrade()
            ->map(fn ($folder) => $this->upgradeClasses($folder))
            ->flatten();
    }

    private function initUpgrades(): Collection
    {
        return (new Collection(static::$vendors))
            ->map(fn ($vendor) => base_path('vendor'.DIRECTORY_SEPARATOR.$vendor))
            ->map(fn ($vendor) => File::directories($vendor))
            ->flatten()
            ->concat((new Collection(static::$folders))->map(fn ($folder) => base_path($folder)))
            ->map(fn ($path) => new Package($path))
            ->filter->isPackage();
    }

    private function upgradeClasses(Package $package): Collection
    {
        return $package->upgradeClasses()
            ->filter(fn ($class) => $this->isUpgrade($class))
            ->map(fn ($class) => new $class)
            ->map(fn ($upgrade) => $upgrade instanceof MigratesStructure
                ? new Structure($upgrade)
                : new $upgrade);
    }

    private function isUpgrade($class): bool
    {
        $reflection = new ReflectionClass($class);

        return $reflection->implementsInterface(MigratesStructure::class)
            || $reflection->implementsInterface(Upgrade::class);
    }
}
