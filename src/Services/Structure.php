<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Roles\Models\Role;

class Structure extends CustomUpgrade
{
    private Collection $existing;
    private Collection $roles;

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
                ->reject(fn ($role) => $role->name === Config::get('enso.config.defaultRole'))
                ->each->writeConfig();
        }
    }

    private function storeWithRoles(array $permission): void
    {
        $permission = Permission::create($permission);

        $permission->roles()
            ->sync($this->syncRoles($permission));
    }

    private function syncRoles(Permission $permission): Collection
    {
        return $this->roles()->when(! $permission->is_default, fn ($roles) => $roles->filter(fn ($role) => in_array($role->name, $this->upgrade->roles())
            || $role->name === Config::get('enso.config.defaultRole')));
    }

    private function roles(): Collection
    {
        return $this->roles ??= Role::get();
    }
}
