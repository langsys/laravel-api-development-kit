<?php

namespace Langsys\ApiKit\Data\ResourceMetadata;

use Langsys\ApiKit\Contracts\ResourceMetadataResolver;

class ConfigResolver implements ResourceMetadataResolver
{
    public function getFilterableFields(string $resourceName): array
    {
        return config("api-kit.resources.{$resourceName}.filterable", []);
    }

    public function getOrderableFields(string $resourceName): array
    {
        return config("api-kit.resources.{$resourceName}.orderable", []);
    }

    public function getDefaultOrder(string $resourceName): array
    {
        $defaultOrder = config("api-kit.resources.{$resourceName}.default_order", []);

        if (empty($defaultOrder)) {
            return [];
        }

        // Convert ['field' => 'direction'] format to [['field', 'direction']] format
        $result = [];
        foreach ($defaultOrder as $field => $direction) {
            $result[] = [$field, $direction];
        }

        return $result;
    }

    public function getDefaultFilters(string $resourceName): array
    {
        return config("api-kit.resources.{$resourceName}.default_filters", []);
    }
}
