<?php

namespace Langsys\ApiKit\Notifications\Channels;

use Langsys\ApiKit\Contracts\ErrorNotificationChannel;
use Langsys\ApiKit\Data\ErrorData;
use Illuminate\Support\Facades\Http;

class TeamsChannel implements ErrorNotificationChannel
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

        $timestamp = date('Y-m-d H:i:s', $error->createdAt);

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'summary' => "[{$error->appName}] Error Report",
            'themeColor' => 'FF0000',
            'title' => "[{$error->appName}] Error Report",
            'sections' => [
                [
                    'facts' => [
                        ['name' => 'Message', 'value' => $error->message],
                        ['name' => 'File', 'value' => "{$error->file}:{$error->line}"],
                        ['name' => 'URL', 'value' => $error->url],
                        ['name' => 'Method', 'value' => $error->method],
                        ['name' => 'Timestamp', 'value' => $timestamp],
                    ],
                ],
            ],
        ];

        Http::post($webhookUrl, $payload);
    }
}
