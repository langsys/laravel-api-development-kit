<?php

namespace Langsys\ApiKit\Middleware;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Http\Request;
use Langsys\ApiKit\Data\BaseData;
use Spatie\LaravelData\Data;

class BooleanQueryParamCast
{
    private const array VALID_BOOLEAN_VALUES = ['true', 'false', '1', '0', 1, 0];
    private const array VALID_TRUE_VALUES = ['true', '1', 1];

    public function handle(Request $request, Closure $next)
    {
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        $route = $request->route();
        if (!$route) {
            return $next($request);
        }

        $controller = $route->getController();
        $method = $route->getActionMethod();

        try {
            $reflection = new ReflectionMethod($controller, $method);
            $parameters = $reflection->getParameters();

            foreach ($parameters as $parameter) {
                if (!$this->isLaravelDataRequest($parameter)) {
                    continue;
                }

                $this->castBooleanProperties($parameter, $request);
            }
        } catch (\ReflectionException $e) {
            return $next($request);
        }

        return $next($request);
    }

    private function isLaravelDataRequest(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();
        if (!$type || !$type instanceof \ReflectionNamedType) {
            return false;
        }

        $class = $type->getName();
        if (!class_exists($class)) {
            return false;
        }

        return is_subclass_of($class, BaseData::class) || is_subclass_of($class, Data::class);
    }

    private function castBooleanProperties(ReflectionParameter $parameter, Request $request): void
    {
        $type = $parameter->getType();
        $class = $type->getName();

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return;
        }

        $properties = $constructor->getParameters();
        foreach ($properties as $property) {
            if (!$property->hasType() ||
                !$property->getType() instanceof \ReflectionNamedType ||
                $property->getType()->getName() !== 'bool') {
                continue;
            }

            $name = $property->getName();
            if (!$request->has($name)) {
                continue;
            }

            $value = $request->query($name);
            if (is_bool($value)) {
                continue;
            }

            if (!in_array($value, self::VALID_BOOLEAN_VALUES, true)) {
                continue;
            }

            $request->query->set($name, in_array($value, self::VALID_TRUE_VALUES, true));
        }
    }
}
