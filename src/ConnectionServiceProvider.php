<?php

namespace LaravelEnso\Upgrade;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use LaravelEnso\Upgrade\Services\DBAL\Connection;

class ConnectionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(Connection::class, fn () => new Connection());
    }

    public function provides(): array
    {
        return [Connection::class];
    }
}
