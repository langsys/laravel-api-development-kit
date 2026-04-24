<?php

namespace Langsys\ApiKit\Traits;

use Langsys\ApiKit\Contracts\ResourceMetadataResolver;
use Langsys\ApiKit\Data\FilterByCondition;
use Langsys\ApiKit\Data\FilterByItem;
use Illuminate\Support\Collection;

trait FilterableCollection
{
    private const NON_NULL_VALUES = ['!null', 'not_null'];

    private const COMPARISON_OPERATORS = ['>', '<', '>=', '<='];

    protected function applyFiltering(Collection $collection, ?string $resourceClass = null): Collection
    {
        if ($collection->isEmpty() || !$resourceClass) {
            return $collection;
        }

        $resolver = app(ResourceMetadataResolver::class);
        $resourceName = class_basename($resourceClass);

        $filterableFields = $resolver->getFilterableFields($resourceName);

        if (empty($filterableFields)) {
            return $collection;
        }

        $filterBy = request()->get('filter_by');
        $filters = $this->_resolveFilters($filterBy, $filterableFields, $resolver, $resourceName);

        if (empty($filters)) {
            return $collection;
        }

        return $this->_processAndApplyFilters($collection, $filters, $filterableFields);
    }

    private function _resolveFilters(mixed $filterBy, array $filterableFields, ResourceMetadataResolver $resolver, string $resourceName): array
    {
        if ($filterBy && !empty($filterBy)) {
            $filterItems = is_array($filterBy) ? $filterBy : [$filterBy];
            $filters = $this->_parseFilterBy($filterItems);

            return array_intersect_key($filters, $filterableFields);
        }

        $defaultFilters = $resolver->getDefaultFilters($resourceName);

        if (empty($defaultFilters)) {
            return [];
        }

        $conditions = [];
        foreach ($defaultFilters as $field => $value) {
            $conditions[$field] = new FilterByCondition('=', $value);
        }

        return $conditions;
    }

    private function _processAndApplyFilters(Collection $collection, array $filters, array $filterableFields): Collection
    {
        $validFilters = [];

        foreach ($filters as $fieldName => $condition) {
            if ($condition->operator === '!null') {
                $validFilters[$fieldName] = $condition;
                continue;
            }

            $fieldTypeInfo = $filterableFields[$fieldName] ?? null;

            if ($this->_isComparisonOperator($condition->operator) && !$this->_canCompare($condition, $fieldTypeInfo)) {
                continue;
            }

            try {
                $convertedValue = $this->_validateAndConvertFilterValue($condition->value, $fieldTypeInfo);
                $validFilters[$fieldName] = new FilterByCondition($condition->operator, $convertedValue);
            } catch (\Throwable) {
                continue;
            }
        }

        if (empty($validFilters)) {
            return $collection;
        }

        return $collection->filter(function ($item) use ($validFilters) {
            foreach ($validFilters as $fieldName => $condition) {
                if (!$condition->matches(data_get($item, $fieldName))) {
                    return false;
                }
            }
            return true;
        });
    }

    private function _isComparisonOperator(string $operator): bool
    {
        return in_array($operator, self::COMPARISON_OPERATORS, true);
    }

    private function _canCompare(FilterByCondition $condition, ?array $fieldTypeInfo): bool
    {
        if (!$fieldTypeInfo) {
            return false;
        }

        $type = $fieldTypeInfo['type'] ?? null;

        if (in_array($type, ['int', 'float'], true)) {
            return true;
        }

        return $type === 'string' && is_numeric($condition->value);
    }

    /**
     * Parse filter_by parameter into array of field => FilterByCondition
     * Supports formats:
     * - filter_by[]=field:value          (equality)
     * - filter_by[]=field:!null          (non-null check)
     * - filter_by[]=field:>:value        (comparison operators: >, <, >=, <=)
     */
    private function _parseFilterBy(array $filterBy): array
    {
        $filters = [];

        foreach ($filterBy as $filterItem) {
            if (!is_string($filterItem) || $filterItem === '') {
                continue;
            }

            $parsed = $this->_parseFilterItem($filterItem);
            if ($parsed) {
                $filters[$parsed->field] = $parsed->condition;
            }
        }

        return $filters;
    }

    private function _parseFilterItem(string $filterItem): ?FilterByItem
    {
        $parts = array_map('trim', explode(':', $filterItem, 3));

        $fieldName = $parts[0] ?? '';
        $a = $parts[1] ?? null;
        $b = $parts[2] ?? null;

        if ($fieldName === '' || $a === null) {
            return null;
        }

        if ($b === null) {
            return new FilterByItem($fieldName, $this->_conditionFromValue($a));
        }

        if ($this->_isComparisonOperator($a) && $b !== '') {
            return new FilterByItem($fieldName, new FilterByCondition($a, $b));
        }

        if ($a === '=') {
            return new FilterByItem($fieldName, $this->_conditionFromValue($b));
        }

        return new FilterByItem($fieldName, $this->_conditionFromValue("$a:$b"));
    }

    private function _conditionFromValue(string $value): FilterByCondition
    {
        if (in_array(strtolower($value), self::NON_NULL_VALUES, true)) {
            return new FilterByCondition('!null');
        }

        return new FilterByCondition('=', $value);
    }

    private function _validateAndConvertFilterValue(?string $value, ?array $fieldTypeInfo): mixed
    {
        if (!$fieldTypeInfo || !$this->_isValidFilterValue($value, $fieldTypeInfo)) {
            throw new \Exception('Invalid filter value');
        }

        if ($value === null || $value === 'null') {
            return null;
        }

        $fieldType = $fieldTypeInfo['type'] ?? 'string';

        return match ($fieldType) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => in_array(strtolower($value), ['true', '1'], true),
            'enum' => $this->_convertToEnum($value, $fieldTypeInfo['enum'] ?? null),
            default => $value,
        };
    }

    private function _isValidFilterValue(?string $value, array $fieldTypeInfo): bool
    {
        $fieldType = $fieldTypeInfo['type'] ?? 'string';

        if ($value === null || $value === 'null') {
            return true;
        }

        return match ($fieldType) {
            'bool' => in_array(strtolower($value), ['true', 'false', '1', '0'], true),
            'int' => is_numeric($value) && (int) $value == $value,
            'float' => is_numeric($value),
            'string' => true,
            'enum' => $this->_isValidEnumValue($value, $fieldTypeInfo['enum'] ?? null),
            default => false,
        };
    }

    private function _isValidEnumValue(string $value, ?string $enumClass): bool
    {
        if (!$enumClass || !class_exists($enumClass) || !is_subclass_of($enumClass, \BackedEnum::class)) {
            return false;
        }

        $reflection = new \ReflectionEnum($enumClass);
        $backingType = $reflection->getBackingType();

        $castedValue = $backingType && $backingType->getName() === 'int'
            ? (int) $value
            : $value;

        return in_array($castedValue, array_column($enumClass::cases(), 'value'), true);
    }

    private function _convertToEnum(?string $value, ?string $enumClass): mixed
    {
        if ($value === null || !$enumClass || !class_exists($enumClass) || !is_subclass_of($enumClass, \BackedEnum::class)) {
            return $value;
        }

        $reflection = new \ReflectionEnum($enumClass);
        $backingType = $reflection->getBackingType();

        if (!$backingType) {
            return $value;
        }

        $castedValue = match ($backingType->getName()) {
            'int' => (int) $value,
            default => $value,
        };

        return $enumClass::from($castedValue);
    }
}
