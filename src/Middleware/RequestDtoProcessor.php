<?php

namespace Langsys\ApiKit\Middleware;

use Closure;
use ReflectionClass;
use Spatie\LaravelData\Data;
use Langsys\ApiKit\Attributes\CastInput;
use Langsys\ApiKit\Attributes\BindTo;
use Langsys\ApiKit\Contracts\ValidatesWhenMissing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Attributes\Validation\CustomValidationAttribute;
use Spatie\LaravelData\Support\Validation\ValidationPath;

class RequestDtoProcessor
{
    public function handle($request, Closure $next)
    {
        $dtoClass = $this->getDtoClassFromRoute($request);
        if (!$dtoClass) {
            return $next($request);
        }

        $input = $request->all();

        $result = $this->validateAndCastDtoFields($dtoClass, $input);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        $request->replace($result);
        return $next($request);
    }

    private function validateAndCastDtoFields(string $dtoClass, array $requestProperties): array|JsonResponse
    {
        $request = request();
        $reflection = new ReflectionClass($dtoClass);
        $rules = [];
        $boundModels = [];

        foreach ($reflection->getProperties() as $property) {
            $fieldName = $property->getName();
            $type = $property->getType();
            $typeName = $type?->getName();

            // Handle model binding with BindTo attribute
            $bindToAttributes = $property->getAttributes(BindTo::class);
            if (!empty($bindToAttributes)) {
                $bindTo = $bindToAttributes[0]->newInstance();
                $idFieldName = $fieldName;
                $modelPropertyName = $this->getModelPropertyName($idFieldName);

                if (isset($requestProperties[$idFieldName])) {
                    $modelClass = $bindTo->modelClass;
                    $model = $modelClass::find($requestProperties[$idFieldName]);
                    $boundModels[$modelPropertyName] = $model;
                }
            }

            // Collect validation rules from CustomValidationAttribute attributes
            $validationAttributes = $property->getAttributes(CustomValidationAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($validationAttributes as $attribute) {
                $attributeInstance = $attribute->newInstance();

                if ((!array_key_exists($fieldName, $requestProperties) || $requestProperties[$fieldName] === null) && $attributeInstance instanceof ValidatesWhenMissing) {
                    $validationRules = $attributeInstance->getRules(ValidationPath::create($fieldName));
                    $requestProperties[$fieldName] = null;

                    if (!empty($validationRules)) {
                        $rules[$fieldName] = array_merge($rules[$fieldName] ?? [], (array) $validationRules);
                    }
                }
            }

            // Cast fields with CastInput attribute
            if (!empty($property->getAttributes(CastInput::class)) && isset($requestProperties[$fieldName]) && $requestProperties[$fieldName] !== null && $typeName) {
                $requestProperties[$fieldName] = $this->castValue($requestProperties[$fieldName], $typeName);
            }

            // Recursively cast nested DTOs
            if ($typeName && is_subclass_of($typeName, Data::class) && isset($requestProperties[$fieldName]) && is_array($requestProperties[$fieldName])) {
                $nestedResult = $this->validateAndCastDtoFields($typeName, $requestProperties[$fieldName]);
                $requestProperties[$fieldName] = $nestedResult;
            }
        }

        if (!empty($rules)) {
            $validator = Validator::make($requestProperties, $rules);

            if ($validator->fails()) {
                throw ValidationException::withMessages($validator->errors()->toArray());
            }
        }

        foreach ($boundModels as $propertyName => $model) {
            $requestProperties[$propertyName] = $model;
        }

        $request->attributes->set('_bound_models', $boundModels);

        return $requestProperties;
    }

    private function getDtoClassFromRoute($request): ?string
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $action = $route->getAction();
        if (!isset($action['controller'])) {
            return null;
        }

        [$controllerClass, $method] = explode('@', $action['controller']);
        $reflection = new \ReflectionMethod($controllerClass, $method);

        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $paramClass = $type->getName();
                if (is_subclass_of($paramClass, Data::class)) {
                    return $paramClass;
                }
            }
        }
        return null;
    }

    private function castValue($value, string $type)
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'string' => (string) $value,
            default => $value,
        };
    }

    private function getModelPropertyName(string $idFieldName): string
    {
        if (str_ends_with($idFieldName, '_id')) {
            $withoutId = substr($idFieldName, 0, -3);
            return $this->snakeToCamel($withoutId);
        }

        return $this->snakeToCamel($idFieldName);
    }

    private function snakeToCamel(string $snake): string
    {
        return lcfirst(str_replace('_', '', ucwords($snake, '_')));
    }
}
