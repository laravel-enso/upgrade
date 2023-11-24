<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Roles\Models\Role;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Contracts\RenamesMigrations;
use LaravelEnso\Upgrade\Services\Database;
use LaravelEnso\Upgrade\Services\Structure;
use LaravelEnso\Upgrade\Traits\StructureMigration;
use Tests\TestCase;

class MigrationsUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected RenamesMigrations $upgrade;
    protected $defaultRole;
    protected $secondaryRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upgrade = new TestStructureMigration();

        $this->defaultRole = $this->role(Config::get('enso.config.defaultRole'));

        $this->secondaryRole = $this->role('secondaryRole');
    }

    /** @test */
    public function can_migrate()
    {
        $this->upgrade->permissions = [
            ['name' => 'test', 'description' => 'test', 'is_default' => true],
        ];

        $this->migrateStructure();

        $this->assertTrue(Permission::whereName('test')->exists());
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

    protected function role($name)
    {
        return Role::factory()->create([
            'name' => $name,
        ]);
    }

    private function migrateStructure()
    {
        (new Database(new Structure($this->upgrade)))->handle();
    }
}

class TestStructureMigration implements MigratesStructure
{
    use StructureMigration;

    public $permissions = [];
}
