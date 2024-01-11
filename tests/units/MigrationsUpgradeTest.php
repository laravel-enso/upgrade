<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\Helpers\Exceptions\EnsoException;
use LaravelEnso\Upgrade\Contracts\RenamesMigrations;
use LaravelEnso\Upgrade\Services\Database;
use LaravelEnso\Upgrade\Services\Migrations;
use Tests\TestCase;

class MigrationsUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock = $this->createMock(TestRenamesMigrations::class);
    }

    /** @test */
    public function can_rename_migrations()
    {
        $this->createMigration('bar');

        $this->mock->method('from')->willReturn(['bar']);
        $this->mock->method('to')->willReturn(['foo']);

        $this->migrateStructure();

        $this->assertTrue(DB::table('migrations')->whereMigration('foo')->exists());
    }

    /** @test */
    public function will_throw_exception_on_argument_count_mismatch()
    {
        $this->expectException(EnsoException::class);

        $this->mock->method('to')->willReturn(['foo']);
        $this->mock->method('from')->willReturn(['foo', 'bar']);

        $this->migrateStructure();
    }

    /** @test */
    public function will_not_migrate_data_if_all_to_migrations_exist()
    {
        $this->createMigration('foo');
        $this->createMigration('qux');

        $this->mock->method('from')->willReturn(['bar', 'baz']);
        $this->mock->method('to')->willReturn(['foo', 'qux']);

        $service = Mockery::mock(Migrations::class, [$this->mock]);
        $service->expects()->class()->andReturn($this->mock)->twice();
        $service->expects()->isMigrated()->andReturn(true);

        (new Database($service))->handle();
    }

    private function migrateStructure()
    {
        (new Database(new Migrations($this->mock)))->handle();
    }

    private function createMigration(string $name): void
    {
        DB::table('migrations')->insert([
            'migration' => $name,
            'batch' => 1,
        ]);
    }
}

class TestRenamesMigrations implements RenamesMigrations
{
    public function from(): array
    {
        return [];
    }

    public function to(): array
    {
        return [];
    }
}
