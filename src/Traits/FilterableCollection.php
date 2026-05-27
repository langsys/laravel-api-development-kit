<?php

namespace Langsys\ApiKit\Traits;

use Langsys\ApiKit\Contracts\ResourceMetadataResolver;
use Langsys\ApiKit\Data\FieldType;
use Langsys\ApiKit\Data\FilterByCondition;
use Illuminate\Support\Collection;

trait FilterableCollection
{
    private const COMPARISON_OPS  = ['>', '<', '>=', '<='];
    private const NUMERIC_TYPES   = ['int', 'float'];
    private const VALUELESS_OPS   = ['null', '!null'];
    private const DATE_FORMAT_REGEX = '/^(\d{4})-(\d{2})-(\d{2})$/';
    private const DATE_FIELD_SUFFIXES = ['_at', '_date'];

    /**
     * Apply filtering to a resource collection based on the filter_by query parameter.
     */
    protected function applyFiltering(Collection $collection, ?string $resourceClass = null): Collection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        $resourceClass ??= $this->inferResourceClassFromCollection($collection);
        if (!$resourceClass) {
            return $collection;
        }

        $resourceName = class_basename($resourceClass);
        $resolver = app(ResourceMetadataResolver::class);
        $filterableFields = $resolver->getFilterableFields($resourceName);

        if (empty($filterableFields)) {
            return $collection;
        }

        $filters = $this->resolveFilters($filterableFields, $resolver, $resourceName);
        if (empty($filters)) {
            return $collection;
        }

        $fieldTypes = $this->buildFieldTypes($filterableFields);
        $validFilters = $this->convertFilters($filters, $fieldTypes);
        if (empty($validFilters)) {
            return $collection;
        }

        return $collection->filter(function ($item) use ($validFilters) {
            foreach ($validFilters as $field => $condition) {
                $actual = data_get($item, $field);
                if (!$condition->matches($actual)) {
                    return false;
                }
            }
            return true;
        });
    }

    private function inferResourceClassFromCollection(Collection $collection): ?string
    {
        if ($collection->isEmpty()) {
            return null;
        }

        $first = $collection->first();
        return is_object($first) ? get_class($first) : null;
    }

    private function buildFieldTypes(array $filterableFields): array
    {
        $types = [];
        foreach ($filterableFields as $field => $def) {
            $types[$field] = $this->makeFieldType($field, is_array($def) ? $def : []);
        }
        return $types;
    }

    private function makeFieldType(string $name, array $def): FieldType
    {
        $type = $def['type'] ?? 'string';
        $enum = $def['enum'] ?? null;

        if ($type === 'enum' && is_string($enum) && class_exists($enum)) {
            $type = $enum; // normalize so castValue + matches treat FQCN as the type
        }

        $isDate = $def['is_date'] ?? false;
        if (!$isDate) {
            foreach (self::DATE_FIELD_SUFFIXES as $suffix) {
                if (str_ends_with($name, $suffix)) {
                    $isDate = true;
                    break;
                }
            }
        }

        return new FieldType(
            type: $type,
            nullable: $def['nullable'] ?? true,
            is_date: $isDate,
        );
    }

    /**
     * Resolve filters from the request, or fall back to defaults stored for the resource.
     * Returns an array keyed by field name with un-converted FilterByCondition values.
     */
    private function resolveFilters(array $filterableFields, ResourceMetadataResolver $resolver, string $resourceName): array
    {
        $filterBy = request()->get('filter_by');

        if (empty($filterBy)) {
            $defaultFilters = $resolver->getDefaultFilters($resourceName);
            if (empty($defaultFilters)) {
                return [];
            }

            $conditions = [];
            foreach ($defaultFilters as $field => $value) {
                $conditions[$field] = strtolower((string) $value) === 'null'
                    ? new FilterByCondition('null')
                    : new FilterByCondition('=', $value);
            }

            return array_intersect_key($conditions, $filterableFields);
        }

        $parsed = [];
        foreach ((array) $filterBy as $item) {
            if (!is_string($item)) {
                continue;
            }
            $result = $this->parseFilterItem($item);
            if ($result === null) {
                continue;
            }
            [$field, $condition] = $result;
            $parsed[$field] = $condition;
        }

        return array_intersect_key($parsed, $filterableFields);
    }

    /**
     * Parse a single filter_by item. Returns [field, FilterByCondition] or null on malformed input.
     * Grammar:
     *   field:value          -> equality
     *   field:null           -> is-null check
     *   field:!null          -> not-null check
     *   field:<op>:value     -> comparison (<op> in >, <, >=, <=)
     */
    private function parseFilterItem(string $item): ?array
    {
        $parts = array_map('trim', explode(':', $item, 3));
        $field = $parts[0] ?? '';
        $a     = $parts[1] ?? '';
        $b     = $parts[2] ?? null;

        if ($field === '' || $a === '') {
            return null;
        }

        if ($b !== null) {
            if (!in_array($a, self::COMPARISON_OPS, true) || $b === '') {
                return null;
            }
            return [$field, new FilterByCondition($a, $b)];
        }

        $op = strtolower($a);
        if (in_array($op, self::VALUELESS_OPS, true)) {
            return [$field, new FilterByCondition($op)];
        }

        return [$field, new FilterByCondition('=', $a)];
    }

    /**
     * Type-check and cast filter values against the Resource's typed properties.
     * Filters that can't be converted are dropped silently.
     */
    private function convertFilters(array $filters, array $fieldTypes): array
    {
        $valid = [];
        foreach ($filters as $field => $condition) {
            $fieldType = $fieldTypes[$field] ?? null;
            if (!$fieldType) {
                continue;
            }
            $converted = $this->convertCondition($condition, $fieldType);
            if ($converted !== null) {
                $valid[$field] = $converted;
            }
        }
        return $valid;
    }

    private function convertCondition(FilterByCondition $condition, FieldType $fieldType): ?FilterByCondition
    {
        $isComparison = in_array($condition->operator, self::COMPARISON_OPS, true);

        // Date comparison: only triggers when the property is a date field
        // (CoC `_at`/`_date` suffix or explicit is_date) and the value
        // matches strict YYYY-MM-DD AND is a real calendar date.
        if ($isComparison && $fieldType->is_date && is_string($condition->value)
            && preg_match(self::DATE_FORMAT_REGEX, $condition->value, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
        ) {
            return new FilterByCondition(
                $condition->operator,
                strtotime($condition->value),
                compare_as_timestamp: $fieldType->type === 'string',
            );
        }

        if ($isComparison && !in_array($fieldType->type, self::NUMERIC_TYPES, true)) {
            // Non-numeric (and non-date) comparisons are not supported.
            return null;
        }

        if (!$this->validateFilterValueForType($condition->value, $fieldType)) {
            return null;
        }

        // null / !null carry no value to cast — preserve the condition as-is
        // so matches() can use its operator-side comparison.
        if (in_array($condition->operator, self::VALUELESS_OPS, true)) {
            return $condition;
        }

        return new FilterByCondition($condition->operator, $this->castValue($condition->value, $fieldType->type));
    }

    private function validateFilterValueForType(?string $value, FieldType $fieldType): bool
    {
        if ($value === null || strtolower((string) $value) === 'null') {
            return $fieldType->nullable;
        }

        $type = $fieldType->type;

        switch ($type) {
            case 'bool':
                $validBooleanValues = ['true', 'false', '1', '0'];
                return in_array(strtolower($value), $validBooleanValues);
            case 'int':
                return is_numeric($value) && (int)$value == $value;
            case 'float':
                return is_numeric($value);
            case 'string':
                return true;
            default:
                if (class_exists($type) && is_subclass_of($type, \BackedEnum::class)) {
                    $enumValues = array_column($type::cases(), 'value');
                    $castedValue = $this->castValueToEnumType($value, $type);
                    return in_array($castedValue, $enumValues);
                }
                return false;
        }
    }

    private function castValueToEnumType(?string $value, string $enumClass): mixed
    {
        if ($value === null) {
            return null;
        }

        $reflection = new \ReflectionEnum($enumClass);
        $backingType = $reflection->getBackingType();

        if (!$backingType) {
            return $value;
        }

        return match ($backingType->getName()) {
            'int' => (int) $value,
            'string' => $value,
            default => $value
        };
    }

    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => in_array(strtolower($value), ['true', '1'], true),
            'string' => $value,
            default  => (class_exists($type) && is_subclass_of($type, \BackedEnum::class))
                ? $type::from($this->castValueToEnumType($value, $type))
                : $value,
        };
    }
}
