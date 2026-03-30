<?php

namespace Langsys\ApiKit\Notifications\Channels;

use Langsys\ApiKit\Contracts\ErrorNotificationChannel;
use Langsys\ApiKit\Data\ErrorData;
use Illuminate\Support\Facades\Http;

class SlackChannel implements ErrorNotificationChannel
{
    public function __construct(
        protected array $config = []
    ) {}

    public function send(ErrorData $error): void
    {
        $webhookUrl = $this->config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'text' => $this->formatMessage($error),
        ];

        Http::post($webhookUrl, $payload);
    }

    protected function formatMessage(ErrorData $error): string
    {
        $timestamp = date('Y-m-d H:i:s', $error->createdAt);

        return implode("\n", [
            "*[{$error->appName}] Error Report*",
            "",
            "> *Message:* {$error->message}",
            "> *File:* {$error->file}:{$error->line}",
            "> *URL:* {$error->url}",
            "> *Method:* {$error->method}",
            "> *Timestamp:* {$timestamp}",
        ]);
    }
}
