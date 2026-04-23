<?php

namespace LaravelEnso\Upgrade\Testing;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

trait InteractsWithUpgradeFixtures
{
    private array $upgradeConfig = [];

    protected function setUpUpgradeFixture(): void
    {
        File::copyDirectory($this->upgradeStubsPath(), $this->upgradeFixturePath());
        clearstatcache();

        $this->registerUpgradeFixtureNamespace();

        $this->upgradeConfig = [
            'folders' => Config::get('enso.upgrade.folders'),
            'vendors' => Config::get('enso.upgrade.vendors'),
            'dateTimeFormat' => Config::get('enso.config.dateTimeFormat'),
        ];
    }

    protected function tearDownUpgradeFixture(): void
    {
        File::deleteDirectory($this->upgradeFixturePath());
        clearstatcache();

        Config::set('enso.upgrade.folders', $this->upgradeConfig['folders']);
        Config::set('enso.upgrade.vendors', $this->upgradeConfig['vendors']);
        Config::set('enso.config.dateTimeFormat', $this->upgradeConfig['dateTimeFormat']);
    }

    protected function registerUpgradeFixtureNamespace(): void
    {
        $loader = require base_path('vendor/autoload.php');

        $loader->setPsr4('LaravelEnso\\TestUpgrade\\', $this->upgradeFixturePath('src'));
    }

    protected function configureUpgradeFixtureDiscovery(): void
    {
        Config::set('enso.upgrade.folders', [$this->upgradeFixtureRelativePath()]);
        Config::set('enso.upgrade.vendors', []);
    }

    protected function upgradeFixturePath(string $path = ''): string
    {
        $base = storage_path('framework/testing/upgrade-fixtures/'
            .$this->upgradeFixtureToken().'/test-upgrades');

        return $path === '' ? $base : "{$base}/{$path}";
    }

    protected function upgradeFixtureRelativePath(): string
    {
        return 'storage/framework/testing/upgrade-fixtures/'
            .$this->upgradeFixtureToken().'/test-upgrades';
    }

    protected function emptyUpgradeFixturePath(string $path = ''): string
    {
        $base = storage_path('framework/testing/upgrade-fixtures/'
            .$this->upgradeFixtureToken().'/empty-package');

        return $path === '' ? $base : "{$base}/{$path}";
    }

    protected function upgradeFixtureToken(): string
    {
        return (string) (env('TEST_TOKEN') ?: getmypid());
    }

    private function upgradeStubsPath(): string
    {
        return dirname(__DIR__, 2).'/tests/stubs';
    }
}
