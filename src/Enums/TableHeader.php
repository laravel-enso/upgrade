<?php

namespace LaravelEnso\Upgrade\Enums;

use Illuminate\Support\Str;
use LaravelEnso\Enums\Traits\Enum;

enum TableHeader: int
{
    use Enum;

    case NrCrt = 1;
    case Package = 2;
    case Upgrade = 3;
    case Applicable = 4;
    case Manual = 5;
    case Priority = 6;
    case Migration = 7;
    case Ran = 8;
    case LastModifiedAt = 9;

    public static function labels(): array
    {
        return array_map(fn ($case) => $case->label(), self::cases());
    }

    public function label(): string
    {
        return Str::of($this->name)->snake(' ')->title();
    }
}
