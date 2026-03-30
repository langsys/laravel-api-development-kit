<?php

namespace Langsys\ApiKit\Contracts;

use Langsys\ApiKit\Data\ExceptionResponse;
use Illuminate\Http\Request;
use Throwable;

interface ExceptionHandleable
{
    public function handle(Request $request, Throwable $exception): ExceptionResponse|array;
    public function getStatusCode(): int;
}
