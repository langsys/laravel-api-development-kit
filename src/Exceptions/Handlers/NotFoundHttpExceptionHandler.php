<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class NotFoundHttpExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::NOT_FOUND->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        $message = $exception->getMessage() ?: 'The requested resource was not found.';

        return new ExceptionResponse($message, $this->statusCode);
    }
}
