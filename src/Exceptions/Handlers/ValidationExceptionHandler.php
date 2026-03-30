<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ValidationExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::UNPROCESSABLE_ENTITY->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        /** @var ValidationException $exception */
        $errors = $exception->errors();
        $firstError = collect($errors)->flatten()->first() ?? 'Validation failed.';

        return new ExceptionResponse($firstError, $this->statusCode);
    }
}
