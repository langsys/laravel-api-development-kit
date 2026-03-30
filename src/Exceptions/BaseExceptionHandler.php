<?php

namespace Langsys\ApiKit\Exceptions;

use Langsys\ApiKit\Contracts\ExceptionHandleable;
use Langsys\ApiKit\Data\ExceptionResponse;
use Langsys\ApiKit\Data\ErrorData;
use Langsys\ApiKit\Enums\HttpCode;
use Langsys\ApiKit\Notifications\ErrorNotificationService;
use Illuminate\Http\Request;
use Throwable;

abstract class BaseExceptionHandler implements ExceptionHandleable
{
    protected int $statusCode = HttpCode::INTERNAL_SERVER_ERROR->value;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    protected function reportError(Throwable $exception): void
    {
        $reportOnCodes = config('api-kit.error_notifications.report_on_status_codes', [500, 502]);

        if (in_array($this->statusCode, $reportOnCodes)) {
            $errorData = new ErrorData(
                message: $exception->getMessage(),
                file: $exception->getFile(),
                line: $exception->getLine(),
            );
            app(ErrorNotificationService::class)->handleError($errorData);
        }
    }

    public function buildErrorArray(Request $request, Throwable $exception): array
    {
        return [
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'message' => $exception->getMessage(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'payload' => json_encode($request->all()),
        ];
    }

    abstract public function handle(Request $request, Throwable $exception): ExceptionResponse|array;
}
