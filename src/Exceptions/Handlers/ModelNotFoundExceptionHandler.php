<?php

namespace Langsys\ApiKit\Exceptions\Handlers;

use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Exceptions\BaseExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ModelNotFoundExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::NOT_FOUND->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        /** @var ModelNotFoundException $exception */
        $model = $exception->getModel();
        $readableName = Str::snake(class_basename($model), ' ');
        $readableName = ucfirst($readableName);

        $ids = $exception->getIds();
        if (!empty($ids)) {
            $message = "{$readableName} not found with ID(s): " . implode(', ', $ids) . '.';
        } else {
            $message = "{$readableName} not found.";
        }

        return new ExceptionResponse($message, $this->statusCode);
    }
}
