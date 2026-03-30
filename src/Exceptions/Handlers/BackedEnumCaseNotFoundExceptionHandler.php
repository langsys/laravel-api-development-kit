<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class BackedEnumCaseNotFoundExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::NOT_FOUND->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        $message = 'The requested value does not match any valid option.';

        return new ExceptionResponse($message, $this->statusCode);
    }
}
