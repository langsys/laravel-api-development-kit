<?php

namespace Langsys\ApiKit\Contracts;

interface ResourceMetadataResolver
{
    /**
     * Get filterable fields for a resource.
     * Returns array of field_name => ['type' => 'string|int|float|bool|enum', 'enum' => EnumClass::class]
     */
    public function getFilterableFields(string $resourceName): array;

    /**
     * Get orderable field names for a resource.
     * Returns array of field names.
     */
    public function getOrderableFields(string $resourceName): array;

    /**
     * Get default order for a resource.
     * Returns array of [field, direction] pairs.
     */
    public function getDefaultOrder(string $resourceName): array;

    /**
     * Get default filters for a resource.
     * Returns array of field_name => value pairs.
     */
    public function getDefaultFilters(string $resourceName): array;
}
