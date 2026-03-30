<?php

namespace Langsys\ApiKit\Exceptions;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Illuminate\Http\Request;
use Throwable;

class DefaultExceptionHandler extends BaseExceptionHandler
{
    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        $this->reportError($exception);

        $message = !config('app.debug') && $this->statusCode >= 500
            ? 'An internal error occurred.'
            : $exception->getMessage();

        return new ExceptionResponse($message, $this->statusCode);
    }
}
