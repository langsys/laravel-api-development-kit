<?php

namespace Langsys\ApiKit\Exceptions;

use Langsys\ApiKit\Contracts\ExceptionHandleable;
use Throwable;

class ExceptionHandlerFactory
{
    private const PACKAGE_HANDLER_NAMESPACE = 'Langsys\\ApiKit\\Exceptions\\Handlers\\';

    public static function resolve(Throwable $exception): ExceptionHandleable
    {
        $exceptionClass = get_class($exception);
        $handlerName = class_basename($exceptionClass) . 'Handler';

        // Check consumer app namespace first
        $appNamespace = config('api-kit.exception_handler_namespace')
            ?? app()->getNamespace() . 'Exceptions\\Handlers\\';
        $appHandlerClass = $appNamespace . $handlerName;

        if (class_exists($appHandlerClass) && is_subclass_of($appHandlerClass, ExceptionHandleable::class)) {
            return new $appHandlerClass();
        }

        // Check package namespace
        $packageHandlerClass = self::PACKAGE_HANDLER_NAMESPACE . $handlerName;

        if (class_exists($packageHandlerClass)) {
            return new $packageHandlerClass();
        }

        return new DefaultExceptionHandler();
    }
}
