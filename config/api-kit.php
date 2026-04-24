<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resource Metadata Driver
    |--------------------------------------------------------------------------
    |
    | Determines how filterable/orderable field metadata is resolved for each
    | API resource. The "config" driver reads definitions from the 'resources'
    | array below, while the "database" driver stores metadata in a database
    | table, allowing runtime changes without redeploying.
    |
    | Supported: "config", "database"
    |
    */
    'resource_driver' => env('API_KIT_RESOURCE_DRIVER', 'config'),

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Define filterable/orderable fields per resource when using the "config"
    | driver. Each key should match the resource name used in your API (e.g.
    | the plural model name). Inside each resource you may declare:
    |
    |   - filterable:       An associative array of field names to their type
    |                       definitions. Supported types include "string",
    |                       "integer", "boolean", "date", and "enum". When
    |                       using "enum", supply the fully-qualified enum
    |                       class via the 'enum' key.
    |
    |   - orderable:        A flat array of column names the client is allowed
    |                       to sort by.
    |
    |   - default_order:    An associative array of column => direction pairs
    |                       applied when the client does not request a
    |                       specific sort order.
    |
    |   - default_filters:  An associative array of field => value pairs
    |                       applied when the client does not supply filters.
    |
    | Example:
    | 'users' => [
    |     'filterable' => [
    |         'status' => ['type' => 'string'],
    |         'role'   => ['type' => 'enum', 'enum' => App\Enums\Role::class],
    |     ],
    |     'orderable'       => ['created_at', 'name', 'email'],
    |     'default_order'   => ['created_at' => 'desc'],
    |     'default_filters' => ['status' => 'active'],
    | ],
    |
    */
    'resources' => [],

    /*
    |--------------------------------------------------------------------------
    | Resource Metadata (database driver)
    |--------------------------------------------------------------------------
    |
    | When using the "database" driver, the resolver reads metadata from the
    | ApiResource eloquent model. Override "model" if your application stores
    | ApiResource records on its own class. The model must expose the
    | standard relations: orderableFields, filterableFields,
    | defaultOrderEntries, defaultFilters.
    |
    | "resource_namespace" is used to reflect on Resource DTO constructors
    | to infer filterable field types when the filterable_fields table does
    | not store field_type / enum_class columns.
    |
    */
    'resource_metadata' => [
        'model' => \Langsys\ApiKit\ApiKey\Models\ApiResource::class,
        'resource_namespace' => 'App\\Http\\Resources\\',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Controls the default pagination behaviour for list endpoints. The
    | "default_records_per_page" value is used whenever the client does not
    | explicitly request a page size via the query string.
    |
    */
    'pagination' => [
        'default_records_per_page' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Notifications
    |--------------------------------------------------------------------------
    |
    | When enabled, the package can send real-time notifications to a chat
    | channel whenever certain HTTP error codes are returned by your API.
    |
    | - enabled:                 Master switch for error notifications.
    | - channel:                 The channel driver to use (see 'channels').
    | - dedup_ttl_seconds:       Time window in seconds during which duplicate
    |                            errors are suppressed to avoid notification
    |                            storms.
    | - report_on_status_codes:  Only errors matching these HTTP status codes
    |                            will trigger notifications.
    | - channels:                Driver-specific configuration. Each driver
    |                            requires its own credentials / webhook URL.
    |
    */
    'error_notifications' => [
        'enabled' => env('API_KIT_ERROR_NOTIFICATIONS', false),
        'channel' => env('API_KIT_ERROR_CHANNEL', 'slack'),
        'dedup_ttl_seconds' => 5,
        'report_on_status_codes' => [500, 502],
        'channels' => [
            /*
            | Slack — uses an Incoming Webhook URL.
            | See: https://api.slack.com/messaging/webhooks
            */
            'slack' => [
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
            ],

            /*
            | Mattermost — uses an Incoming Webhook URL.
            | See: https://developers.mattermost.com/integrate/webhooks/incoming/
            */
            'mattermost' => [
                'webhook_url' => env('MATTERMOST_WEBHOOK_URL'),
            ],

            /*
            | Microsoft Teams — uses an Incoming Webhook URL.
            | See: https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/
            */
            'teams' => [
                'webhook_url' => env('TEAMS_WEBHOOK_URL'),
            ],

            /*
            | Telegram — requires a Bot API token and the target chat ID.
            | See: https://core.telegram.org/bots/api
            */
            'telegram' => [
                'bot_token' => env('TELEGRAM_BOT_TOKEN'),
                'chat_id' => env('TELEGRAM_CHAT_ID'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Authentication
    |--------------------------------------------------------------------------
    |
    | Provides a simple header-based API key guard for routes that do not
    | require full user authentication (e.g. machine-to-machine calls).
    |
    | - enabled:  Toggle the API key guard on or off.
    | - header:   The HTTP header name the guard will read the key from.
    | - model:    The Eloquent model used to store and validate API keys.
    |             You may replace this with your own model as long as it
    |             implements the same interface.
    |
    */
    'api_key' => [
        'enabled' => env('API_KIT_API_KEY_ENABLED', false),
        'header' => env('API_KIT_API_KEY_HEADER', 'X-Authorization'),
        'model' => \Langsys\ApiKit\ApiKey\ApiKey::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handler Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where the package looks for per-exception handler classes.
    | The package ships its own default handlers and will also scan the
    | application's "App\Exceptions\Handlers" namespace automatically. Set
    | this value only if your handlers live in a non-standard namespace.
    |
    | When null, the package falls back to the App\Exceptions\Handlers
    | namespace.
    |
    */
    'exception_handler_namespace' => null,

    /*
    |--------------------------------------------------------------------------
    | Auth Data
    |--------------------------------------------------------------------------
    |
    | Configuration related to the resolved AuthData object that is available
    | throughout the request lifecycle via the authData() helper.
    |
    | - data_class:         Fully-qualified class used to represent resolved
    |                       auth data. Must extend Langsys\ApiKit\Data\AuthData
    |                       and accept the same constructor signature. Override
    |                       this if your application needs to attach extra
    |                       helpers (e.g. lazy-loaded User / ApiKey lookups).
    |
    | - super_admin_check:  A callable or a fully-qualified class name that
    |                       implements the SuperAdminCheck interface. This is
    |                       used to determine whether the authenticated user
    |                       should bypass all permission checks. Set to null
    |                       to disable super-admin detection.
    |
    */
    'auth' => [
        'data_class' => \Langsys\ApiKit\Data\AuthData::class,
        'super_admin_check' => null,
    ],
];
