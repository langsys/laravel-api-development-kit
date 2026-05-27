<?php

namespace Langsys\ApiKit\Traits;

use Illuminate\Support\Collection;
use Langsys\ApiKit\Contracts\ResourceMetadataResolver;

trait OrderableCollections
{
    private const DEFAULT_ORDERABLE_FIELDS = ['created_at', 'updated_at'];
    private const DEFAULT_ORDER            = [['created_at', 'desc']];
    private const DIRECTIONS               = ['asc', 'desc'];

    protected array $defaultOrderableFields = self::DEFAULT_ORDERABLE_FIELDS;
    protected array $defaultOrder = self::DEFAULT_ORDER;

    /**
     * Apply ordering to a collection based on the order_by query parameter,
     * falling back to the resource's configured default order or the trait default.
     */
    protected function applyOrdering(Collection $collection, ?string $resourceClass = null): Collection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        $orderBy = $this->resolveOrder($resourceClass);
        if (empty($orderBy)) {
            return $collection;
        }

        return $collection->sortBy($orderBy)->values();
    }

    /**
     * Resolve the order spec for this request. User-supplied order_by takes priority;
     * if absent or all invalid, fall back to the resource's default order, then the trait default.
     * Returns an array of [field, direction] tuples.
     */
    private function resolveOrder(?string $resourceClass = null): array
    {
        $orderByParam = request()->input('order_by');

        if (!empty($orderByParam)) {
            $allowed = $this->getAllowedOrderableFields($resourceClass);

            $parsed = [];
            foreach ((array) $orderByParam as $item) {
                if (!is_string($item)) {
                    continue;
                }
                $result = $this->parseOrderItem($item, $allowed);
                if ($result !== null) {
                    $parsed[] = $result;
                }
            }
            if (!empty($parsed)) {
                return $parsed;
            }
        }

        if ($resourceClass) {
            $resolver = app(ResourceMetadataResolver::class);
            $resourceName = class_basename($resourceClass);
            $defaultOrder = $resolver->getDefaultOrder($resourceName);
            if (!empty($defaultOrder)) {
                return $defaultOrder;
            }
        }

        return $this->defaultOrder ?: self::DEFAULT_ORDER;
    }

    /**
     * Parse a single order_by item. Returns [field, direction] or null on malformed input.
     * Grammar:
     *   field           -> ascending
     *   field:asc       -> ascending
     *   field:desc      -> descending
     */
    private function parseOrderItem(string $item, array $allowed): ?array
    {
        $parts     = array_map('trim', explode(':', $item, 2));
        $field     = $parts[0] ?? '';
        $direction = isset($parts[1]) ? strtolower($parts[1]) : 'asc';

        if (!in_array($field, $allowed, true)) {
            return null;
        }
        if (!in_array($direction, self::DIRECTIONS, true)) {
            return null;
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

        $base = $this->defaultOrderableFields ?: self::DEFAULT_ORDERABLE_FIELDS;
        return array_unique(array_merge($base, $resourceOrderableFields));
    }
}
