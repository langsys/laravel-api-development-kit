<?php

namespace Langsys\ApiKit\Tests\Unit;

use Langsys\ApiKit\Exceptions\ExceptionHandlerFactory;
use Langsys\ApiKit\Exceptions\DefaultExceptionHandler;
use Langsys\ApiKit\Exceptions\Handlers\ValidationExceptionHandler;
use Langsys\ApiKit\Tests\TestCase;
use Illuminate\Validation\ValidationException;

class ExceptionHandlerFactoryTest extends TestCase
{
    public function test_resolves_validation_exception_handler(): void
    {
        $exception = ValidationException::withMessages(['field' => ['error']]);

        $handler = ExceptionHandlerFactory::resolve($exception);

        $this->assertInstanceOf(ValidationExceptionHandler::class, $handler);
    }

    public function test_falls_back_to_default_handler_for_unknown_exceptions(): void
    {
        $exception = new \RuntimeException('Something went wrong');

        $handler = ExceptionHandlerFactory::resolve($exception);

        $this->assertInstanceOf(DefaultExceptionHandler::class, $handler);
    }
}
