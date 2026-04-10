<?php

namespace Langsys\ApiKit\Data;

class FilterByCondition extends BaseInternalData
{
    public function __construct(
        public string $operator,
        public mixed $value = null,
    ) {
    }

    public function matches(mixed $itemValue): bool
    {
        return match ($this->operator) {
            '='     => $itemValue === $this->value,
            '!null' => $itemValue !== null,
            '>', '<', '>=', '<=' => $this->_compare($itemValue),
            default => false,
        };
    }

    private function _compare(mixed $itemValue): bool
    {
        $left  = $this->_toComparable($itemValue);
        $right = $this->_toComparable($this->value);

        if ($left === null || $right === null) {
            return false;
        }

        return match ($this->operator) {
            '>'  => $left > $right,
            '<'  => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            default => false,
        };
    }

    private function _toComparable(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return str_contains((string) $value, '.')
                ? (float) $value
                : (int) $value;
        }

        return null;
    }
}
