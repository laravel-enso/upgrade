<?php

namespace LaravelEnso\Upgrade\Services;

use Illuminate\Support\Facades\Config;
use LaravelEnso\Upgrade\Contracts\Applicable;
use LaravelEnso\Upgrade\Contracts\BeforeMigration;
use LaravelEnso\Upgrade\Contracts\ShouldRunManually;
use LaravelEnso\Upgrade\Contracts\Upgrade as Contract;
use LaravelEnso\Upgrade\Enums\TableHeader;

class UpgradeStatus extends Upgrade
{
    public function handle()
    {
        return $this->sorted()->values()->map(fn (Contract $upgrade, $index) => [
            TableHeader::NrCrt->value => $index + 1,
            TableHeader::Package->value => Reflection::package($upgrade),
            TableHeader::Upgrade->value => Reflection::upgrade($upgrade),
            TableHeader::Applicable->value => $this->applicable($upgrade),
            TableHeader::Manual->value => $this->isManual($upgrade),
            TableHeader::Priority->value => $this->priority($upgrade),
            TableHeader::Migration->value => $this->migration($upgrade),
            TableHeader::Ran->value => $this->ran($upgrade),
            TableHeader::LastModifiedAt->value => $this->changedAt($upgrade),
        ]);
    }

    private function applicable(Contract $upgrade): string
    {
        return !$upgrade instanceof Applicable || $upgrade->applicable()
            ? $this->green('yes')
            : $this->yellow('no');
    }

    private function isManual(Contract $upgrade): string
    {
        return $upgrade instanceof ShouldRunManually
            ? $this->yellow('yes')
            : $this->green('no');
    }

    private function changedAt(Contract $upgrade): string
    {
        $lastModifiedAt = Reflection::lastModifiedAt($upgrade);
        $format = Config::get('enso.config.dateTimeFormat');

        return "{$lastModifiedAt->format($format)} ({$lastModifiedAt->diffForHumans()})";
    }

    private function migration(Contract $upgrade): string
    {
        return $upgrade instanceof BeforeMigration
            ? $this->yellow('before')
            : $this->green('after');
    }

    private function ran(Contract $upgrade): string
    {
        return $upgrade->isMigrated()
            ? $this->green('yes')
            : $this->red('no');
    }

    private function green($label): string
    {
        return "<info>{$label}</info>";
    }

    private function red($label): string
    {
        return "<fg=red>{$label}</fg=red>";
    }

    private function yellow($label): string
    {
        return "<fg=yellow>{$label}</fg=yellow>";
    }
}
