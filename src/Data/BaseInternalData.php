<?php

namespace Langsys\ApiKit\Data;

abstract class BaseInternalData
{
    public function toArray(?array $except = []): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $array = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            if (in_array($name, $except)) {
                continue;
            }

            $value = $this->{$name};

            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $value = $value->name;
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            $array[$name] = $value;
        }

        return $array;
    }

    public function toJson(array $except = []): string
    {
        return json_encode($this->toArray($except));
    }
}
