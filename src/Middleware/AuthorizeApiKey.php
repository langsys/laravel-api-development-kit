<?php

namespace Langsys\ApiKit\Middleware;

use Closure;
use Illuminate\Http\Request;
use Langsys\ApiKit\Enums\HttpCode;
use Illuminate\Support\Str;

class AuthorizeApiKey
{
    public function handle(Request $request, Closure $next)
    {
        // Allow Sanctum-authenticated requests through
        if ($request->header('Authorization') && $request->user()) {
            return $next($request);
        }

        $headerName = config('api-kit.api_key.header', 'X-Authorization');
        $apiKeyModelClass = config('api-kit.api_key.model');

        if (!$apiKeyModelClass) {
            return response()->json(['error' => 'API key authentication not configured.'], HttpCode::INTERNAL_SERVER_ERROR->value);
        }

        $key = $request->header($headerName);

        if (!$key) {
            return response()->json(['error' => 'API key is required.'], HttpCode::UNAUTHORIZED->value);
        }

        $apiKey = $apiKeyModelClass::getByKey($key);

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key.'], HttpCode::UNAUTHORIZED->value);
        }

        if (!$apiKey->is_active) {
            return response()->json(['error' => 'API key is inactive.'], HttpCode::FORBIDDEN->value);
        }

        // Generate request ID for tracing
        $requestId = (string) Str::uuid();
        $request->headers->set('X-Request-ID', $requestId);

        return $next($request);
    }
}
