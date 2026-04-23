<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use LaravelEnso\TestUpgrade\Upgrades\Deep\DeepUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\POPO;
use LaravelEnso\TestUpgrade\Upgrades\SimpleUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\StructureUpgrade;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Services\Finder;
use LaravelEnso\Upgrade\Services\Structure;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FinderTest extends TestCase
{
    use RefreshDatabase;

    protected MigratesStructure $upgrade;
    protected $defaultRole;
    protected $secondaryRole;
    private array $folders = [];
    private array $vendors = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->folders = Config::get('enso.upgrade.folders', []);
        $this->vendors = Config::get('enso.upgrade.vendors', []);
        File::copyDirectory(__DIR__.'/../stubs', $this->package());
        $this->register();
        Config::set('enso.upgrade.folders', ['vendor/laravel-enso/testUpgrades']);
        Config::set('enso.upgrade.vendors', []);
    }

    public function tearDown(): void
    {
        File::deleteDirectory($this->package());
        Config::set('enso.upgrade.folders', $this->folders);
        Config::set('enso.upgrade.vendors', $this->vendors);

        parent::tearDown();
    }

    #[Test]
    public function should_not_find_classes_dont_implement_contracts()
    {
        $this->assertEmpty($this->getUpgrade(POPO::class));
    }

    #[Test]
    public function can_find_structure_upgrade()
    {
        $structureUpgrade = $this->getUpgrade(Structure::class)
            ->first()->reflection()->getName();

        $this->assertEquals(StructureUpgrade::class, $structureUpgrade);
    }

    #[Test]
    public function can_find_regular_upgrade()
    {
        $this->assertNotEmpty($this->getUpgrade(SimpleUpgrade::class));
    }

    #[Test]
    public function can_find_deep_upgrade()
    {
        $this->assertNotEmpty($this->getUpgrade(DeepUpgrade::class));
    }

    protected function register(): void
    {
        $loader = require base_path().'/vendor/autoload.php';
        $loader->setPsr4(
            'LaravelEnso\TestUpgrade\\',
            $this->package('src')
        );
    }

    protected function getUpgrade(string $class): Collection
    {
        return (new Finder())->upgrades()
            ->filter(fn ($upgrade) => $upgrade::class === $class);
    }

    private function package(...$folders): string
    {
        $relative = Collection::wrap($folders)
            ->prepend('vendor/laravel-enso/testUpgrades')
            ->implode('/');

        return base_path($relative);
    }
}
