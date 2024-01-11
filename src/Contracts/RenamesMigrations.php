<?php

namespace LaravelEnso\Upgrade\Contracts;

interface RenamesMigrations
{
    public function from(): array;

    public function to(): array;
}
