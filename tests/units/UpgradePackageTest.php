<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LaravelEnso\Upgrade\Services\Package;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpgradePackageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::copyDirectory(__DIR__.'/../stubs', $this->package());
        $this->register();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        File::deleteDirectory($this->package());
        File::deleteDirectory($this->emptyPackage());
    }

    #[Test]
    public function qualifies_packages_that_have_a_composer_file_and_upgrades_folder(): void
    {
        $this->assertTrue((new Package($this->package()))->qualifies());
    }

    #[Test]
    public function does_not_qualify_packages_without_an_upgrades_folder(): void
    {
        File::ensureDirectoryExists($this->emptyPackage('src'));
        File::put($this->emptyPackage('composer.json'), json_encode([
            'autoload' => ['psr-4' => ['LaravelEnso\\EmptyPackage\\' => 'src/']],
        ], JSON_THROW_ON_ERROR));

        $this->assertFalse((new Package($this->emptyPackage()))->qualifies());
    }

    #[Test]
    public function returns_only_upgrade_classes_from_the_package(): void
    {
        $classes = (new Package($this->package()))->upgradeClasses()->values();

        $this->assertSame([
            'LaravelEnso\\TestUpgrade\\Upgrades\\CommandManualBeforeUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\Deep\\DeepUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\InapplicableStatusUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\ManualBeforeRanUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\SimpleUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\StructureUpgrade',
        ], $classes->sort()->values()->all());
    }

    private function register(): void
    {
        $loader = require base_path().'/vendor/autoload.php';
        $loader->setPsr4(
            'LaravelEnso\TestUpgrade\\',
            $this->package('src')
        );
    }

    private function package(string $path = ''): string
    {
        $base = base_path('vendor/laravel-enso/testUpgrades');

        return $path === '' ? $base : "{$base}/{$path}";
    }

    private function emptyPackage(string $path = ''): string
    {
        $base = base_path('vendor/laravel-enso/testEmptyUpgradePackage');

        return $path === '' ? $base : "{$base}/{$path}";
    }
}
