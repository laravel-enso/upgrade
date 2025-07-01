<?php

namespace LaravelEnso\Upgrade\Contracts;

interface MigratesStructure
{
    public function permissions(): array;

    public function roles(): array;
}
