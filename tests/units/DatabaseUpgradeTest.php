<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Contracts\Applicable;
use LaravelEnso\Upgrade\Contracts\MigratesData;
use LaravelEnso\Upgrade\Contracts\MigratesPostDataMigration;
use LaravelEnso\Upgrade\Contracts\MigratesTable;
use LaravelEnso\Upgrade\Contracts\Prioritization;
use LaravelEnso\Upgrade\Contracts\RollbackTableMigration;
use LaravelEnso\Upgrade\Helpers\Table;
use LaravelEnso\Upgrade\Services\Finder;
use LaravelEnso\Upgrade\Services\Upgrade as Service;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseUpgradeTest extends TestCase
{
    public static array $calls = [];
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();

        static::$calls = [];
    }

    #[Test]
    public function can_upgrade()
    {
        (new Service($this->finder(TestDatabaseMigration::class)))->handle();

        $this->assertTrue(Table::hasColumn('test', 'name'));
        $this->assertTrue(Table::hasColumn('test', 'post_migration'));
        $this->assertNotEmpty(DB::table('test')->get());
    }

    #[Test]
    public function can_upgrade_with_priorities()
    {
        (new Service($this->finder(
            PriorityDefault::class,
            PrioritizationHighest::class,
        )))->handle();

        $this->assertEquals([
            PrioritizationHighest::class, PriorityDefault::class,
        ], static::$calls);
    }

    #[Test]
    public function cannot_migrate_when_data_migration_fails()
    {
        $this->expectException(Exception::class);

        (new Service($this->finder(FailingDataMigrationTest::class)))->handle();

        $this->assertFalse(Schema::hasTable('test'));
    }

    #[Test]
    public function cannot_migrate_when_post_data_migration_fails()
    {
        $this->expectException(Exception::class);

        (new Service($this->finder(FailingPostMigrationTest::class)))->handle();

        $this->assertFalse(Schema::hasTable('test'));
    }

    #[Test]
    public function cannot_migrate_twice()
    {
        (new Service($this->finder(AlreadyMigratedMigrationTest::class)))->handle();

        $this->assertFalse(Schema::hasTable('test'));
    }

    #[Test]
    public function cannot_migrate_not_applicable()
    {
        (new Service($this->finder(NotApplicable::class)))->handle();

        $this->assertNotContains(NotApplicable::class, static::$calls);
    }

    private function finder(...$classes)
    {
        $upgrades = Collection::wrap($classes)
            ->map(fn ($class) => new $class());

        return Mockery::mock(Finder::class)
            ->allows(['upgrades' => $upgrades]);
    }
}

class TestDatabaseMigration implements MigratesTable, MigratesData, MigratesPostDataMigration, RollbackTableMigration
{
    public function isMigrated(): bool
    {
        return Schema::hasTable('test')
            && DB::table('test')->whereName('model')->exists();
    }

    public function migrateTable(): void
    {
        Schema::create('test', function ($table) {
            $table->string('name');
        });
    }

    public function migrateData(): void
    {
        DB::insert('INSERT INTO test VALUES("model")');
    }

    public function rollbackTableMigration(): void
    {
        Schema::dropIfExists('test');
    }

    public function migratePostDataMigration(): void
    {
        Schema::table('test', function ($table) {
            $table->string('post_migration')->nullable();
        });
    }
}

class FailingDataMigrationTest extends TestDatabaseMigration
{
    public function migrateData(): void
    {
        parent::migrateData();

        throw new Exception();
    }
}

class FailingPostMigrationTest extends TestDatabaseMigration
{
    public function migratePostDataMigration(): void
    {
        parent::migratePostDataMigration();

        throw new Exception();
    }
}

class AlreadyMigratedMigrationTest extends TestDatabaseMigration
{
    public function isMigrated(): bool
    {
        return true;
    }
}

class PrioritizationHighest implements Prioritization, MigratesTable
{
    public function isMigrated(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 0;
    }

    public function migrateTable(): void
    {
        DatabaseUpgradeTest::$calls[] = static::class;
    }
}

class PriorityDefault implements MigratesTable
{
    public function isMigrated(): bool
    {
        return false;
    }

    public function migrateTable(): void
    {
        DatabaseUpgradeTest::$calls[] = static::class;
    }
}

class NotApplicable implements MigratesTable, Applicable
{
    public function isMigrated(): bool
    {
        return false;
    }

    public function migrateTable(): void
    {
        DatabaseUpgradeTest::$calls[] = static::class;
    }

    public function applicable(): bool
    {
        return false;
    }
}
