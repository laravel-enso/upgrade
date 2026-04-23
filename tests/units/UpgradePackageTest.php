<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LaravelEnso\Upgrade\Services\Package;
use LaravelEnso\Upgrade\Testing\InteractsWithUpgradeFixtures;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpgradePackageTest extends TestCase
{
    use InteractsWithUpgradeFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpUpgradeFixture();
    }

    protected function tearDown(): void
    {
        $this->tearDownUpgradeFixture();

        File::deleteDirectory($this->emptyUpgradeFixturePath());

        parent::tearDown();
    }

    #[Test]
    public function qualifies_packages_that_have_a_composer_file_and_upgrades_folder(): void
    {
        $this->assertTrue((new Package($this->upgradeFixturePath()))->qualifies());
    }

    #[Test]
    public function does_not_qualify_packages_without_an_upgrades_folder(): void
    {
        File::ensureDirectoryExists($this->emptyUpgradeFixturePath('src'));
        File::put($this->emptyUpgradeFixturePath('composer.json'), json_encode([
            'autoload' => ['psr-4' => ['LaravelEnso\\EmptyPackage\\' => 'src/']],
        ], JSON_THROW_ON_ERROR));

        $this->assertFalse((new Package($this->emptyUpgradeFixturePath()))->qualifies());
    }

    #[Test]
    public function returns_only_upgrade_classes_from_the_package(): void
    {
        $classes = (new Package($this->upgradeFixturePath()))->upgradeClasses()->values();

        $this->assertSame([
            'LaravelEnso\\TestUpgrade\\Upgrades\\CommandManualBeforeUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\Deep\\DeepUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\InapplicableStatusUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\ManualBeforeRanUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\SimpleUpgrade',
            'LaravelEnso\\TestUpgrade\\Upgrades\\StructureUpgrade',
        ], $classes->sort()->values()->all());
    }

}
