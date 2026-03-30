<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class MethodNotAllowedHttpExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::METHOD_NOT_ALLOWED->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        $message = 'The ' . $request->method() . ' method is not supported for this route.';

        return new ExceptionResponse($message, $this->statusCode);
    }
}
