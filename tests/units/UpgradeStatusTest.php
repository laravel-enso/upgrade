<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use LaravelEnso\TestUpgrade\Upgrades\Deep\DeepUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\InapplicableStatusUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\ManualBeforeRanUpgrade;
use LaravelEnso\TestUpgrade\Upgrades\SimpleUpgrade;
use LaravelEnso\Upgrade\Enums\TableHeader;
use LaravelEnso\Upgrade\Services\Finder;
use LaravelEnso\Upgrade\Services\UpgradeStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpgradeStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::copyDirectory(__DIR__.'/../stubs', $this->package());
        $this->register();
        Config::set('enso.config.dateTimeFormat', 'Y-m-d H:i:s');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        File::deleteDirectory($this->package());
    }

    #[Test]
    public function orders_upgrades_by_priority_and_last_modified_at(): void
    {
        $simpleFile = $this->package('src/Upgrades/SimpleUpgrade.php');
        $deepFile = $this->package('src/Upgrades/Deep/DeepUpgrade.php');

        touch($simpleFile, Carbon::parse('2024-01-01 10:00:00')->timestamp);
        touch($deepFile, Carbon::parse('2024-01-01 11:00:00')->timestamp);

        $rows = $this->upgradeStatus(
            new SimpleUpgrade(),
            new DeepUpgrade(),
            new ManualBeforeRanUpgrade(),
        )->handle();

        $this->assertSame([
            'manual before ran upgrade',
            'deep upgrade',
            'simple upgrade',
        ], $rows->pluck(TableHeader::Upgrade)->all());
    }

    #[Test]
    public function formats_manual_applicable_migration_and_ran_columns(): void
    {
        $manualRow = $this->rowsByUpgrade()['manual before ran upgrade'];
        $inapplicableRow = $this->rowsByUpgrade()['inapplicable status upgrade'];

        $this->assertSame('test-upgrade', $manualRow[TableHeader::Package]);
        $this->assertSame('<fg=yellow>yes</fg=yellow>', $manualRow[TableHeader::Manual]);
        $this->assertSame('<fg=yellow>before</fg=yellow>', $manualRow[TableHeader::Migration]);
        $this->assertSame('<info>yes</info>', $manualRow[TableHeader::Ran]);
        $this->assertSame(5, $manualRow[TableHeader::Priority]);

        $this->assertSame('<fg=yellow>no</fg=yellow>', $inapplicableRow[TableHeader::Applicable]);
        $this->assertSame('<info>no</info>', $inapplicableRow[TableHeader::Manual]);
        $this->assertSame('<info>after</info>', $inapplicableRow[TableHeader::Migration]);
        $this->assertSame('<fg=red>no</fg=red>', $inapplicableRow[TableHeader::Ran]);
        $this->assertSame(20, $inapplicableRow[TableHeader::Priority]);
    }

    #[Test]
    public function includes_the_formatted_last_modified_timestamp(): void
    {
        $file = $this->package('src/Upgrades/ManualBeforeRanUpgrade.php');
        touch($file, Carbon::parse('2024-02-02 09:15:00')->timestamp);

        $row = $this->upgradeStatus(new ManualBeforeRanUpgrade())->handle()->first();
        $formattedTimestamp = Carbon::createFromTimestamp(filemtime($file))
            ->format('Y-m-d H:i:s');

        $this->assertStringContainsString($formattedTimestamp, $row[TableHeader::LastModifiedAt]);
        $this->assertStringContainsString('(', $row[TableHeader::LastModifiedAt]);
        $this->assertStringContainsString(')', $row[TableHeader::LastModifiedAt]);
    }

    private function upgradeStatus(object ...$upgrades): UpgradeStatus
    {
        return new UpgradeStatus($this->finder(...$upgrades));
    }

    private function rowsByUpgrade(): array
    {
        return $this->upgradeStatus(
            new ManualBeforeRanUpgrade(),
            new InapplicableStatusUpgrade(),
        )->handle()->keyBy(TableHeader::Upgrade)->all();
    }

    private function finder(object ...$upgrades): Finder
    {
        return Mockery::mock(Finder::class)
            ->allows(['upgrades' => Collection::wrap($upgrades)]);
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
}
