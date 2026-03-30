<?php

namespace Langsys\ApiKit\Data;

use Langsys\ApiKit\Enums\AuthorizableType;

class AuthData extends BaseInternalData
{
    public function __construct(
        public string $authId,
        public AuthorizableType $authType = AuthorizableType::USER,
    ) {}
}
