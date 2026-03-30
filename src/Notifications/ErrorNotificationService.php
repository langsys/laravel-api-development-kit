<?php

namespace Langsys\ApiKit\Notifications;

use Langsys\ApiKit\Data\ErrorData;
use Langsys\ApiKit\Contracts\ErrorNotificationChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ErrorNotificationService
{
    public function handleError(ErrorData $error): void
    {
        $this->logError($error);

        if (!config('api-kit.error_notifications.enabled', false)) {
            return;
        }

        $ttl = config('api-kit.error_notifications.dedup_ttl_seconds', 5);
        $cacheKey = "api_kit_error:{$error->signature()}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addSeconds($ttl));

        try {
            $channel = app(ErrorNotificationChannel::class);
            $channel->send($error);
        } catch (\Exception $e) {
            Log::warning('Failed to send error notification: ' . $e->getMessage());
        }
    }

    public function logError(ErrorData $error): void
    {
        Log::error(json_encode($error->toArray()));
    }
}
