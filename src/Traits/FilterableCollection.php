<?php

namespace Langsys\ApiKit\Traits;

use Illuminate\Support\Collection;
use Langsys\ApiKit\Contracts\ResourceMetadataResolver;

trait FilterableCollection
{
    protected function applyFiltering(Collection $collection, ?string $resourceClass = null): Collection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        if (!$resourceClass) {
            return $collection;
        }

        $resolver = app(ResourceMetadataResolver::class);
        $resourceName = class_basename($resourceClass);

        $filterableFields = $resolver->getFilterableFields($resourceName);

        if (empty($filterableFields)) {
            return $collection;
        }

        $request = request();
        $filterBy = $request->get('filter_by');

        if ($filterBy && !empty($filterBy)) {
            $filterItems = is_array($filterBy) ? $filterBy : [$filterBy];
            $filters = $this->parseFilterBy($filterItems);
            // Only keep filters that are allowed
            $filters = array_intersect_key($filters, $filterableFields);
        } else {
            $defaultFilters = $resolver->getDefaultFilters($resourceName);
            if (empty($defaultFilters)) {
                return $collection;
            }
            $filters = $defaultFilters;
        }

        if (empty($filters)) {
            return $collection;
        }

        return $this->processAndApplyFilters($collection, $filters, $filterableFields);
    }

    protected function processAndApplyFilters(Collection $collection, array $filters, array $filterableFields): Collection
    {
        $validFilters = collect($filters)
            ->mapWithKeys(function ($value, $fieldName) use ($filterableFields) {
                try {
                    $fieldTypeInfo = $filterableFields[$fieldName] ?? null;
                    if (!$fieldTypeInfo) {
                        return [];
                    }
                    $convertedValue = $this->validateAndConvertFilterValue($value, $fieldTypeInfo);
                } catch (\Exception $e) {
                    return [];
                }
                return [$fieldName => $convertedValue];
            })
            ->toArray();

        if (empty($validFilters)) {
            return $collection;
        }

        return $this->applyFiltersToCollection($collection, $validFilters);
    }

    protected function parseFilterBy($filterBy): array
    {
        $filters = [];

        foreach ($filterBy as $filterItem) {
            $parsed = $this->parseFilterItem($filterItem);
            if ($parsed) {
                $filters[$parsed['field']] = $parsed['value'];
            }
        }

        return $filters;
    }

    protected function parseFilterItem(string $filterItem): ?array
    {
        $parts = explode(':', $filterItem, 2);
        if (count($parts) > 1) {
            return ['field' => trim($parts[0]), 'value' => trim($parts[1])];
        }
        return null;
    }

    protected function applyFiltersToCollection(Collection $collection, array $filters): Collection
    {
        return $collection->filter(function ($item) use ($filters) {
            foreach ($filters as $fieldName => $value) {
                if ($item->{$fieldName} !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    protected function validateAndConvertFilterValue(?string $value, ?array $fieldTypeInfo): mixed
    {
        if (!$fieldTypeInfo) {
            throw new \Exception('Unknown field type');
        }

        $fieldType = $fieldTypeInfo['type'] ?? 'string';

        if ($value === null || $value === 'null') {
            return null;
        }

        return match ($fieldType) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => in_array(strtolower($value), ['true', '1']),
            'enum' => $this->convertToEnum($value, $fieldTypeInfo['enum'] ?? null),
            default => $value,
        };
    }

    protected function convertToEnum(?string $value, ?string $enumClass): mixed
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
