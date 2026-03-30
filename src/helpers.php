<?php

use Langsys\ApiKit\Data\AuthData;
use Langsys\ApiKit\Traits\AuthDataResolver;

if (!function_exists('authData')) {
    function authData(): ?AuthData
    {
        static $resolver = null;

        if ($resolver === null) {
            $resolver = new class {
                use AuthDataResolver;
            };
        }

        return $resolver->resolveAuthData();
    }
}
