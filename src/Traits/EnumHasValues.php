<?php

namespace Langsys\ApiKit\Traits;

trait EnumHasValues
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromValue(string $value): self
    {
        return self::from($value);
    }
}
