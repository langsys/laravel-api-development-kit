<?php

namespace Langsys\ApiKit\Notifications\Channels;

use Langsys\ApiKit\Contracts\ErrorNotificationChannel;
use Langsys\ApiKit\Data\ErrorData;
use Illuminate\Support\Facades\Http;

class TelegramChannel implements ErrorNotificationChannel
{
    public function __construct(
        protected array $config = []
    ) {}

    public function send(ErrorData $error): void
    {
        $botToken = $this->config['bot_token'] ?? null;
        $chatId = $this->config['chat_id'] ?? null;

        if (!$botToken || !$chatId) {
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $this->formatMessage($error),
            'parse_mode' => 'HTML',
        ];

        Http::post($url, $payload);
    }

    protected function formatMessage(ErrorData $error): string
    {
        $timestamp = date('Y-m-d H:i:s', $error->createdAt);

        return implode("\n", [
            "<b>[{$error->appName}] Error Report</b>",
            "",
            "<b>Message:</b> " . htmlspecialchars($error->message),
            "<b>File:</b> {$error->file}:{$error->line}",
            "<b>URL:</b> {$error->url}",
            "<b>Method:</b> {$error->method}",
            "<b>Timestamp:</b> {$timestamp}",
        ]);
    }
}
