<?php

namespace Langsys\ApiKit\Data;

abstract class BaseInternalData
{
    public function toArray(bool $includeNulls = true, array $except = [], bool $includeDynamic = false): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();

        $array = [];
        $objectCopy = $includeDynamic ? clone $this : null;

        foreach ($properties as $property) {
            $name = $property->getName();
            $property->setAccessible(true);

            if ($includeDynamic) {
                unset($objectCopy->$name);
            }

            if (!$property->isInitialized($this) || in_array($name, $except)) {
                continue;
            }

            $value = $property->getValue($this);

            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $value = $value->name;
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            if ($includeNulls || $value !== null) {
                $array[$name] = $value;
            }
        }

        if ($includeDynamic) {
            $dynamicProperties = json_decode(json_encode($objectCopy), true);
            $array = [...$array, ...$dynamicProperties];
        }

        return $array;
    }

    public function toJson(array $except = []): string
    {
        return json_encode($this->toArray(except: $except));
    }
}
