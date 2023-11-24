<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\Helpers\Exceptions\EnsoException;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Upgrade\Contracts\RenamesMigrations;
use LaravelEnso\Upgrade\Services\Database;
use LaravelEnso\Upgrade\Services\Migrations;
use Tests\TestCase;

class MigrationsUpgradeTest extends TestCase
{
    use RefreshDatabase;

    // protected RenamesMigrations $upgrade;

    // protected function setUp(): void
    // {
    // parent::setUp();

    // $this->upgrade = new TestRenamesMigrations();
    // }

    /** @test */
    public function will_throw_exception_on_argument_count_mismatch()
    {
        $this->expectException(EnsoException::class);

        $mock = $this->createMock(TestRenamesMigrations::class);

        $mock->method('to')->willReturn(['foo']);
        $mock->method('from')->willReturn(['foo', 'bar']);

        $this->migrateStructure($mock);
    }

    /** @test */
    public function can_migrate_default_permission()
    {
        $this->upgrade->permissions = [
            ['name' => 'test', 'description' => 'test', 'is_default' => true],
        ];

        $this->migrateStructure();

        $this->assertEquals('test', $this->defaultRole->permissions->first()->name);
        $this->assertEquals('test', $this->secondaryRole->permissions->first()->name);
    }

    /** @test */
    public function can_migrate_non_default_permission()
    {
        $this->upgrade->permissions = [
            ['name' => 'test', 'description' => 'test', 'is_default' => false],
        ];

        $this->migrateStructure();

        $this->assertEquals('test', $this->defaultRole->permissions->first()->name);
        $this->assertEmpty($this->secondaryRole->permissions);
    }

    /** @test */
    public function skips_existing_permissions()
    {
        $this->upgrade->permissions = [
            ['name' => 'test', 'description' => 'test', 'is_default' => true],
        ];

        $this->migrateStructure();
        $this->migrateStructure();

        $this->assertEquals(1, Permission::whereName('test')->count());
    }

    private function migrateStructure($mock)
    {
        (new Database(new Migrations($mock)))->handle();
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
