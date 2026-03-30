<?php

namespace Langsys\ApiKit\Data\ResourceMetadata;

use Langsys\ApiKit\Contracts\ResourceMetadataResolver;
use Langsys\ApiKit\ApiKey\Models\ApiResource;

class DatabaseResolver implements ResourceMetadataResolver
{
    public function getFilterableFields(string $resourceName): array
    {
        $apiResource = ApiResource::where('name', $resourceName)->first();

        if (!$apiResource) {
            return [];
        }

        return $apiResource->filterableFields
            ->mapWithKeys(fn ($field) => [
                $field->field_name => [
                    'type' => $field->field_type ?? 'string',
                    'enum' => $field->enum_class,
                ],
            ])
            ->toArray();
    }

    public function getOrderableFields(string $resourceName): array
    {
        $apiResource = ApiResource::where('name', $resourceName)->first();

        if (!$apiResource) {
            return [];
        }

        return $apiResource->orderableFields->pluck('field_name')->toArray();
    }

    public function getDefaultOrder(string $resourceName): array
    {
        $apiResource = ApiResource::where('name', $resourceName)->first();

        if (!$apiResource || !$apiResource->defaultOrderEntries || $apiResource->defaultOrderEntries->isEmpty()) {
            return [];
        }

        return $apiResource->defaultOrderEntries->map(fn ($entry) => [
            $entry->orderableField->field_name,
            $entry->direction,
        ])->toArray();
    }

    public function getDefaultFilters(string $resourceName): array
    {
        $apiResource = ApiResource::where('name', $resourceName)->first();

        if (!$apiResource) {
            return [];
        }

        $defaultFilters = $apiResource->defaultFilters;

        if (!$defaultFilters || $defaultFilters->isEmpty()) {
            return [];
        }

        return $defaultFilters->mapWithKeys(fn ($filter) => [
            $filter->filterableField->field_name => $filter->filter_value,
        ])->toArray();
    }
}
