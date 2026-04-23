<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Testing\InteractsWithUpgradeFixtures;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUpgradeFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpUpgradeFixture();
        $this->configureUpgradeFixtureDiscovery();
        Config::set('enso.config.dateTimeFormat', 'Y-m-d H:i:s');
    }

    protected function tearDown(): void
    {
        $this->tearDownUpgradeFixture();

        parent::tearDown();
    }

    #[Test]
    public function status_command_displays_the_upgrade_table(): void
    {
        $this->artisan('enso:upgrade:status')
            ->expectsOutputToContain('Package')
            ->expectsOutputToContain('manual before ran upgrade')
            ->expectsOutputToContain('inapplicable status upgrade')
            ->assertExitCode(0);
    }

    #[Test]
    public function upgrade_command_honors_manual_and_before_migration_options(): void
    {
        $this->artisan('enso:upgrade')->assertExitCode(0);
        $this->assertFalse(Schema::hasTable('command_manual_before_upgrades'));

        $this->artisan('enso:upgrade', [
            '--manual' => true,
            '--before-migration' => true,
        ])->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('command_manual_before_upgrades'));
    }

}
