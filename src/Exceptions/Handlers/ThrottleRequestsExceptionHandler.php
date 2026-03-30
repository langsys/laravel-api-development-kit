<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class ThrottleRequestsExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::TOO_MANY_REQUESTS->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        $this->reportError($exception);

        return new ExceptionResponse('Too many requests. Please try again later.', $this->statusCode);
    }
}
