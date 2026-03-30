<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class AuthenticationExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::UNAUTHORIZED->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        return new ExceptionResponse('Unauthenticated.', $this->statusCode);
    }
}
