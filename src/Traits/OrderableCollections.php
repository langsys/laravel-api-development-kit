<?php

namespace Langsys\ApiKit\Traits;

use Illuminate\Support\Collection;
use Langsys\ApiKit\Contracts\ResourceMetadataResolver;

trait OrderableCollections
{
    protected array $defaultOrderableFields = [
        'created_at',
        'updated_at',
    ];

    protected array $defaultOrder = [
        ['created_at', 'desc'],
    ];

    protected function applyOrdering(Collection $collection, ?string $resourceClass = null): Collection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        $orderBy = $this->parseOrderByQuery($resourceClass);

        if (empty($orderBy)) {
            $orderBy = $this->getDefaultOrderForResource($resourceClass);
        }

        return $collection->sortBy($orderBy)->values();
    }

    private function parseOrderByQuery(?string $resourceClass = null): array
    {
        $orderByParam = request()->input('order_by');

        if (!$orderByParam) {
            return [];
        }

        $orderCriteria = [];
        $allowedFields = $this->getAllowedOrderableFields($resourceClass);
        $orderItems = is_array($orderByParam) ? $orderByParam : [$orderByParam];

        foreach ($orderItems as $orderItem) {
            $parsed = $this->parseOrderItem($orderItem, $allowedFields);
            if ($parsed) {
                $orderCriteria[] = $parsed;
            }
        }

        return $orderCriteria;
    }

    private function parseOrderItem(string $orderItem, array $allowedFields): ?array
    {
        $parts = explode(':', $orderItem);
        $field = $parts[0];
        $direction = isset($parts[1]) ? strtolower($parts[1]) : 'asc';

        if (!in_array($field, $allowedFields)) {
            return null;
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        return [$field, $direction];
    }

    private function getAllowedOrderableFields(?string $resourceClass = null): array
    {
        $resourceOrderableFields = [];

        if ($resourceClass) {
            $resolver = app(ResourceMetadataResolver::class);
            $resourceName = class_basename($resourceClass);
            $resourceOrderableFields = $resolver->getOrderableFields($resourceName);
        }

        return array_unique(array_merge($this->defaultOrderableFields, $resourceOrderableFields));
    }

    private function getDefaultOrderForResource(?string $resourceClass = null): array
    {
        if ($resourceClass) {
            $resolver = app(ResourceMetadataResolver::class);
            $resourceName = class_basename($resourceClass);
            $defaultOrder = $resolver->getDefaultOrder($resourceName);

            if (!empty($defaultOrder)) {
                return $defaultOrder;
            }
        }

        return $this->defaultOrder;
    }
}
