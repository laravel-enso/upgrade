<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Roles\Models\Role;
use LaravelEnso\Upgrade\Contracts\Applicable;
use LaravelEnso\Upgrade\Contracts\MigratesData;
use LaravelEnso\Upgrade\Contracts\MigratesPostDataMigration;
use LaravelEnso\Upgrade\Contracts\MigratesStructure;
use LaravelEnso\Upgrade\Contracts\Prioritization;
use LaravelEnso\Upgrade\Contracts\Upgrade;
use ReflectionClass;

class Structure implements Upgrade, MigratesData, Prioritization, MigratesPostDataMigration, Applicable
{
    private MigratesStructure $upgrade;
    private Collection $existing;
    private Collection $roles;
    private string $defaultRole;

    public function __construct(MigratesStructure $upgrade)
    {
        $this->upgrade = $upgrade;
        $this->defaultRole = Config::get('enso.config.defaultRole');
    }

    public function isMigrated(): bool
    {
        $permissions = Collection::wrap($this->upgrade->permissions())->pluck('name');

        $this->existing = Permission::whereIn('name', $permissions)->pluck('name');

        return $this->existing->count() >= $permissions->count();
    }

    public function migrateData(): void
    {
        if (App::isLocal()) {
            Artisan::call('enso:roles:sync');
        }

        Collection::wrap($this->upgrade->permissions())
            ->reject(fn ($permission) => $this->existing->contains($permission['name']))
            ->each(fn ($permission) => $this->storeWithRoles($permission));

        if (App::isLocal()) {
            $this->roles()
                ->reject(fn ($role) => $role->name === $this->defaultRole)
                ->each->writeConfig();
        }
    }

    public function reflection()
    {
        return new ReflectionClass($this->upgrade);
    }

    public function priority(): int
    {
        return $this->upgrade instanceof Prioritization
            ? $this->upgrade->priority()
            : Prioritization::Default;
    }

    public function migratePostDataMigration(): void
    {
        if ($this->upgrade instanceof MigratesPostDataMigration) {
            $this->upgrade->migratePostDataMigration();
        }
    }

    public function applicable(): bool
    {
        return !$this->upgrade instanceof Applicable
            || $this->upgrade->applicable();
    }

    private function storeWithRoles(array $permission): void
    {
        $permission = Permission::create($permission);

        $permission->roles()
            ->sync($this->syncRoles($permission));
    }

    private function syncRoles(Permission $permission): Collection
    {
        return $this->roles()->when(!$permission->is_default, fn ($roles) => $roles
            ->filter(fn ($role) => in_array($role->name, $this->upgrade->roles())
                || in_array($role->id, $this->upgrade->roles())
                || $role->name === $this->defaultRole));
    }

    private function roles(): Collection
    {
        return $this->roles ??= Role::get();
    }
}
