<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelEnso\Upgrade\Contracts\Upgrade;
use ReflectionClass;

class Reflection
{
    public static function reflection(Upgrade $upgrade): ReflectionClass
    {
        return $upgrade instanceof Structure || $upgrade instanceof Migrations
            ? new ReflectionClass($upgrade->class())
            : new ReflectionClass($upgrade);
    }

    public static function package(Upgrade $upgrade): string
    {
        $namespace = self::reflection($upgrade)->getName();

        $package = Str::startsWith($namespace, 'App')
            ? 'app'
            : explode('\\', $namespace)[1] ?? null;

        return Str::of($package)->snake()->slug('-');
    }

    public static function upgrade(Upgrade $upgrade): string
    {
        $namespace = self::reflection($upgrade)->getName();

        $upgrade = last(explode('\\', $namespace));

        return Str::of($upgrade)->snake(' ');
    }

    public static function lastModifiedAt(Upgrade $upgrade): Carbon
    {
        $filename = self::reflection($upgrade)->getFileName();

        $lastModified = File::lastModified($filename);

        return Carbon::createFromTimestamp($lastModified);
    }
}
