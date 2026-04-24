<?php

namespace Langsys\ApiKit\Traits;

use Langsys\ApiKit\Data\AuthData;
use Langsys\ApiKit\Enums\AuthorizableType;
use Illuminate\Support\Facades\Auth;

trait AuthDataResolver
{
    public function resolveAuthData(): ?AuthData
    {
        $dataClass = config('api-kit.auth.data_class', AuthData::class);

        if (request()->header('Authorization') && $authId = Auth::id()) {
            return new $dataClass((string) $authId, AuthorizableType::USER);
        }

        $apiKeyHeader = config('api-kit.api_key.header', 'X-Authorization');
        $apiKeyModelClass = config('api-kit.api_key.model');
        $headerValue = request()?->header($apiKeyHeader);

        if ($headerValue && $apiKeyModelClass && method_exists($apiKeyModelClass, 'getByKey')) {
            $apiKey = $apiKeyModelClass::getByKey($headerValue);
            if ($apiKey) {
                return new $dataClass((string) $apiKey->id, AuthorizableType::API_KEY);
            }
        }

        return null;
    }
}
