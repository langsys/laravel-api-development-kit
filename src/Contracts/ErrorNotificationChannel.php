<?php

namespace Langsys\ApiKit\Contracts;

use Langsys\ApiKit\Data\ErrorData;

interface ErrorNotificationChannel
{
    public function send(ErrorData $error): void;
}
