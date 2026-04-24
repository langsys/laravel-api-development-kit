<?php

namespace Langsys\ApiKit\Data\ResourceMetadata;

use Illuminate\Database\Eloquent\Model;
use Langsys\ApiKit\ApiKey\Models\ApiResource as DefaultApiResource;
use Langsys\ApiKit\Contracts\ResourceMetadataResolver;

class DatabaseResolver implements ResourceMetadataResolver
{
    public function getFilterableFields(string $resourceName): array
    {
        $apiResource = $this->_resolveApiResource($resourceName);

        if (!$apiResource) {
            return [];
        }

        $reflectedTypes = $this->_reflectFieldTypes($resourceName);

        return $apiResource->filterableFields
            ->mapWithKeys(function ($field) use ($reflectedTypes) {
                $fieldName = $field->field_name;
                $storedType = $field->field_type ?? null;
                $storedEnum = $field->enum_class ?? null;

                if ($storedType) {
                    return [$fieldName => ['type' => $storedType, 'enum' => $storedEnum]];
                }

                return [$fieldName => $reflectedTypes[$fieldName] ?? ['type' => 'string', 'enum' => null]];
            })
            ->toArray();
    }

    public function getOrderableFields(string $resourceName): array
    {
        $apiResource = $this->_resolveApiResource($resourceName);

        if (!$apiResource) {
            return [];
        }

        return $apiResource->orderableFields->pluck('field_name')->toArray();
    }

    public function getDefaultOrder(string $resourceName): array
    {
        $apiResource = $this->_resolveApiResource($resourceName);

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
        $apiResource = $this->_resolveApiResource($resourceName);

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

    private function _resolveApiResource(string $resourceName): ?Model
    {
        $modelClass = config('api-kit.resource_metadata.model', DefaultApiResource::class);
        $endpoint = request()?->route()?->uri();

        if ($endpoint) {
            $endpointSpecific = $modelClass::where('name', $resourceName)
                ->where('endpoint', $endpoint)
                ->first();

            if ($endpointSpecific) {
                return $endpointSpecific;
            }
        }

        return $modelClass::where('name', $resourceName)
            ->whereNull('endpoint')
            ->first();
    }

    private function _reflectFieldTypes(string $resourceName): array
    {
        $namespace = rtrim(config('api-kit.resource_metadata.resource_namespace', 'App\\Http\\Resources\\'), '\\') . '\\';
        $resourceClass = $namespace . $resourceName;

        if (!class_exists($resourceClass)) {
            return [];
        }

        $reflection = new \ReflectionClass($resourceClass);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return [];
        }

        $types = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                continue;
            }

            if ($type->isBuiltin() && in_array($type->getName(), ['string', 'int', 'float', 'bool'], true)) {
                $types[$parameter->getName()] = ['type' => $type->getName(), 'enum' => null];
                continue;
            }

            $typeName = $type->getName();
            if (class_exists($typeName) && is_subclass_of($typeName, \BackedEnum::class)) {
                $types[$parameter->getName()] = ['type' => 'enum', 'enum' => $typeName];
            }
        }

        return $types;
    }
}
