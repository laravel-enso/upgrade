<?php

namespace LaravelEnso\Upgrade\Enums;

use Illuminate\Support\Collection;
use LaravelEnso\Enums\Contracts\Mappable;


enum TableHeader: int implements Mappable
{
    case NrCrt = 1;
    case Package = 2;
    case Upgrade = 3;
    case Applicable = 4;
    case Manual = 5;
    case Priority = 6;
    case Migration = 7;
    case Ran = 8;
    case LastModifiedAt = 9;

    public function map(): string
    {
        return match ($this) {
            self::NrCrt => 'Nr Crt',
            self::Package => 'Package',
            self::Upgrade => 'Upgrade',
            self::Applicable => 'Applicable',
            self::Manual => 'Manual',
            self::Priority => 'Priority',
            self::Migration => 'Migration',
            self::Ran => 'Ran',
            self::LastModifiedAt => 'Last Modified At',
        };
    }

    public static function mappings(): array
    {
        return Collection::wrap(self::cases())
            ->map(fn ($case) => $case->map())
            ->toArray();
    }
}
