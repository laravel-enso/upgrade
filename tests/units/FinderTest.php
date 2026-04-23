<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use LaravelEnso\TestUpgrade\Upgrades\Deep\DeepUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\POPO;
use LaravelEnso\TestUpgrade\Upgrades\SimpleUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\StructureUpgrade;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Services\Finder;
use LaravelEnso\Upgrade\Services\Structure;
use LaravelEnso\Upgrade\Testing\InteractsWithUpgradeFixtures;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FinderTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUpgradeFixtures;

    protected MigratesStructure $upgrade;
    protected $defaultRole;
    protected $secondaryRole;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpUpgradeFixture();
        $this->configureUpgradeFixtureDiscovery();
    }

    public function tearDown(): void
    {
        $this->tearDownUpgradeFixture();

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

    protected function getUpgrade(string $class): Collection
    {
        return (new Finder())->upgrades()
            ->filter(fn ($upgrade) => $upgrade::class === $class);
    }
}
