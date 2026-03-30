<?php

namespace Langsys\ApiKit;

use Illuminate\Support\ServiceProvider;
use Langsys\ApiKit\Contracts\ResourceMetadataResolver;
use Langsys\ApiKit\Contracts\ErrorNotificationChannel;
use Langsys\ApiKit\Data\ResourceMetadata\ConfigResolver;
use Langsys\ApiKit\Data\ResourceMetadata\DatabaseResolver;
use Langsys\ApiKit\Notifications\ErrorNotificationService;
use Langsys\ApiKit\Notifications\Channels\SlackChannel;
use Langsys\ApiKit\Notifications\Channels\MattermostChannel;
use Langsys\ApiKit\Notifications\Channels\TeamsChannel;
use Langsys\ApiKit\Notifications\Channels\TelegramChannel;
use Langsys\ApiKit\Console\Commands\InstallCommand;
use Langsys\ApiKit\Console\Commands\MakeRequestCommand;
use Langsys\ApiKit\Console\Commands\MakeResourceCommand;
use Langsys\ApiKit\Console\Commands\MakeHandlerCommand;

class ApiKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/api-kit.php', 'api-kit');

        $this->app->singleton(ResourceMetadataResolver::class, function ($app) {
            $driver = config('api-kit.resource_driver', 'config');

            return match ($driver) {
                'database' => new DatabaseResolver(),
                default => new ConfigResolver(),
            };
        });

        $this->app->singleton(ErrorNotificationChannel::class, function ($app) {
            $channel = config('api-kit.error_notifications.channel', 'slack');
            $channels = config('api-kit.error_notifications.channels', []);
            $channelConfig = $channels[$channel] ?? [];

            return match ($channel) {
                'mattermost' => new MattermostChannel($channelConfig),
                'teams' => new TeamsChannel($channelConfig),
                'telegram' => new TelegramChannel($channelConfig),
                default => new SlackChannel($channelConfig),
            };
        });

        $this->app->singleton(ErrorNotificationService::class);
    }

    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../config/api-kit.php' => config_path('api-kit.php'),
        ], 'api-kit-config');

        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'api-kit-migrations');

        // Stubs
        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/api-kit'),
        ], 'api-kit-stubs');

        // Documentation
        $this->publishes([
            __DIR__ . '/../docs/CLAUDE.md' => base_path('CLAUDE.md'),
            __DIR__ . '/../docs/conventions.md' => base_path('docs/conventions.md'),
        ], 'api-kit-docs');

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MakeRequestCommand::class,
                MakeResourceCommand::class,
                MakeHandlerCommand::class,
            ]);
        }
    }
}
