<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LaravelEnso\Helpers\Exceptions\EnsoException;

class Migrations extends CustomUpgrade
{
    public function isMigrated(): bool
    {
        return Collection::wrap($this->class()->to())
            ->every(fn ($to) => DB::table('migrations')->whereMigration($to)->exists());
    }

    public function migrateData(): void
    {
        $to = Collection::wrap($this->class()->to())->sortKeys();
        $from = Collection::wrap($this->class()->from())->sortKeys();

        $invalidMapping = $to->count() !== $from->count()
            || $to->keys()->diff($from->keys())->isNotEmpty();

        if ($invalidMapping) {
            $message = 'Invalid number of elements or distinct keys in "from" and "to" arrays';
            throw new EnsoException($message);
        }

        $to->combine($from)
            ->filter(fn ($from) => DB::table('migrations')
                ->whereMigration($from)
                ->exists())
            ->each(fn ($from, $to) => DB::table('migrations')
                ->whereMigration($from)
                ->update(['migration' => $to]));
    }
}
