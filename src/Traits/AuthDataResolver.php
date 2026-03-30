<?php

namespace Langsys\ApiKit\Traits;

use Langsys\ApiKit\Data\AuthData;
use Langsys\ApiKit\Enums\AuthorizableType;
use Illuminate\Support\Facades\Auth;

trait AuthDataResolver
{
    public function resolveAuthData(): ?AuthData
    {
        if (request()->header('Authorization') && $authId = Auth::id()) {
            return new AuthData((string) $authId, AuthorizableType::USER);
        }

        $apiKeyHeader = config('api-kit.api_key.header', 'X-Authorization');
        $apiKeyModelClass = config('api-kit.api_key.model');
        $headerValue = request()?->header($apiKeyHeader);

        if ($headerValue && $apiKeyModelClass && method_exists($apiKeyModelClass, 'getByKey')) {
            $apiKey = $apiKeyModelClass::getByKey($headerValue);
            if ($apiKey) {
                return new AuthData((string) $apiKey->id, AuthorizableType::API_KEY);
            }
        }

        return null;
    }
}
