<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelEnso\Helpers\Services\JsonReader;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Contracts\RenamesMigrations;
use LaravelEnso\Upgrade\Contracts\Upgrade;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class Package
{
    private string $folder;
    private array $composer;

    public function __construct(string $folder)
    {
        $this->folder = $folder;
    }

    public function qualifies(): bool
    {
        return File::exists("{$this->folder}/composer.json")
            && $this->hasUpgrades();
    }

    public function upgradeClasses()
    {
        return Collection::wrap(File::allFiles($this->upgradeFolder()))
            ->map(fn (SplFileInfo $file) => $this->namespace(
                'Upgrades',
                $file->getRelativePath('Upgrades'),
                $file->getFilenameWithoutExtension()
            ))->filter(fn ($class) => $this->isUpgrade($class));
    }

    private function hasUpgrades(): bool
    {
        return File::isDirectory($this->upgradeFolder());
    }

    private function namespace(...$segments): string
    {
        return Collection::wrap([
            rtrim($this->psr4Namespace(), '\\'), ...$segments,
        ])->filter()->implode('\\');
    }

    private function upgradeFolder(): string
    {
        return $this->appFolder('Upgrades');
    }

    private function appFolder(...$segments): string
    {
        $path = Collection::wrap([$this->folder, $this->psr4Folder(), ...$segments])
            ->implode('/');

        return Str::of($path)->replace('//', '/');
    }

    private function psr4Folder()
    {
        return $this->composer()['autoload']['psr-4'][$this->psr4Namespace()];
    }

    private function psr4Namespace()
    {
        return key($this->composer()['autoload']['psr-4']);
    }

    private function composer(): array
    {
        return $this->composer ??=
            (new JsonReader("{$this->folder}/composer.json"))->array();
    }

    private function isUpgrade($class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        return $reflection->implementsInterface(MigratesStructure::class)
            || $reflection->implementsInterface(RenamesMigrations::class)
            || $reflection->implementsInterface(Upgrade::class);
    }
}
