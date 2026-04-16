<?php

namespace Langsys\ApiKit\Data;

class ExceptionResponse
{
    public function __construct(
        public string|array $message,
        public int $code,
        public ?string $redirect = null
    ) {}

    public function getMessage(): string|array
    {
        return $this->message;
    }
}
