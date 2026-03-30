<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class AccessDeniedHttpExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::FORBIDDEN->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        $message = $exception->getMessage() ?: 'Access denied.';

        return new ExceptionResponse($message, $this->statusCode);
    }
}
