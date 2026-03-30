<?php

namespace Langsys\ApiKit\Enums;

use Langsys\ApiKit\Traits\EnumHasValues;

enum AuthorizableType: string
{
    use EnumHasValues;

    case USER = 'user';
    case API_KEY = 'api_key';
}
