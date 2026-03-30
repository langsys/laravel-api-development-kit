# Laravel API Development Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/langsys/laravel-api-development-kit.svg?style=flat-square)](https://packagist.org/packages/langsys/laravel-api-development-kit)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

A comprehensive Laravel package for building structured, consistent APIs. Provides DTO-based request/response processing, standardized error handling, resource filtering and ordering, API key authentication, and role-based authorization — all with minimal boilerplate.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Spatie Laravel Data 3.x or 4.x

## Installation

```bash
composer require langsys/laravel-api-development-kit
```

Publish the configuration file:

```bash
php artisan api-kit:install
```

This publishes `config/api-kit.php`, stub files, and the `docs/CLAUDE.md` template for AI-assisted development.

## Quick Start

```php
// 1. Create a request DTO
php artisan api-kit:make-request CreateUserRequest

// 2. Create a resource DTO
php artisan api-kit:make-resource UserResource

// 3. Build your controller
use Langsys\ApiKit\Traits\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function store(CreateUserRequest $data): JsonResponse
    {
        $user = User::create($data->filled());
        return $this->resourceResponse(UserResource::from($user), 201);
    }

    public function index(): JsonResponse
    {
        return $this->resourceListResponse(
            UserResource::collect(User::all()),
            paginated: true,
            ordered: true,
            filtered: true
        );
    }
}
```

Register the middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(append: [
        \Langsys\ApiKit\Middleware\RequestDtoProcessor::class,
        \Langsys\ApiKit\Middleware\BooleanQueryParamCast::class,
    ]);
})
```

## Features

### DTO System

Request and resource DTOs extend `Langsys\ApiKit\Data\BaseData` (built on Spatie Laravel Data). Internal data objects extend `BaseInternalData` for a lightweight alternative.

```php
use Langsys\ApiKit\Data\BaseData;

class UpdateUserRequest extends BaseData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
    ) {}
}

// Only update fields that were actually sent
$user->update($data->filled());

// Merge additional data
$user->update($data->filled(['updated_by' => auth()->id()]));
```

#### Custom Attributes

**`#[CastInput]`** — Casts input values to the property type before validation:
```php
#[\Langsys\ApiKit\Attributes\CastInput]
public ?int $quantity = null;  // "5" becomes 5
```

**`#[BindTo]`** — Auto-loads an Eloquent model from the property value:
```php
#[\Langsys\ApiKit\Attributes\BindTo(\App\Models\Project::class)]
public ?string $project_id = null;
```

### Response Pipeline

The `ApiResponse` trait provides consistent JSON response methods:

```php
use Langsys\ApiKit\Traits\ApiResponse;

$this->resourceResponse($resource);           // Single resource
$this->resourceListResponse($collection);     // Collection
$this->successResponse('Done');               // Success message
$this->errorResponse('Not found', 404);       // Error
$this->noContentResponse();                   // 204 No Content
```

#### Query Parameters

List endpoints support filtering, ordering, and pagination out of the box:

```
GET /api/users?filter_by[]=status:active&order_by[]=created_at:desc&page=1&records_per_page=25
```

### Exception Handling

Convention-based exception handler resolution. The factory checks your app namespace first, then falls back to package defaults.

```php
use Langsys\ApiKit\Exceptions\ExceptionHandlerFactory;

// In your exception handler's render method:
$handler = ExceptionHandlerFactory::resolve($exception);
$result = $handler->handle($request, $exception);
return $this->errorResponse($result->getMessage(), $result->code);
```

Generate custom handlers:

```bash
php artisan api-kit:make-handler PaymentFailedException
```

This creates `app/Exceptions/Handlers/PaymentFailedExceptionHandler.php`.

### Resource Metadata

Define filterable and orderable fields per resource using the config driver (zero dependencies) or database driver (runtime changes).

```php
// config/api-kit.php
'resources' => [
    'users' => [
        'filterable' => [
            'status' => ['type' => 'enum', 'enum' => UserStatus::class],
            'role' => ['type' => 'string'],
        ],
        'orderable' => ['created_at', 'name', 'email'],
        'default_order' => ['created_at' => 'desc'],
        'default_filters' => ['status' => 'active'],
    ],
],
```

### Authorization

The `AccessGuardService` provides role-based and API key authorization. Implement the package interfaces on your models:

- `Authorizable` — User model: `hasPermission()`, `isSuperAdmin()`
- `AuthorizableByKey` — API key model: `keyHasPermission()`, `keyBelongsToEntity()`
- `AuthorizableByUser` — User model: `userRoleInEntity()`, `roleHasPermission()`
- `GuardableResource` — Entity models (organizations, teams, projects)

```php
$guard = app(\Langsys\ApiKit\Authorization\AccessGuardService::class);
$guard->authorize('manage_users', $organization);
```

### API Key Authentication

Header-based API key authentication for machine-to-machine communication:

```php
// config/api-kit.php
'api_key' => [
    'enabled' => true,
    'header' => 'X-Authorization',
    'model' => \Langsys\ApiKit\ApiKey\ApiKey::class,
],
```

Apply the middleware to routes:

```php
Route::middleware(\Langsys\ApiKit\Middleware\AuthorizeApiKey::class)
    ->group(function () {
        // Protected routes
    });
```

### Error Notifications

Send real-time error notifications to Slack, Mattermost, Microsoft Teams, or Telegram:

```php
// config/api-kit.php
'error_notifications' => [
    'enabled' => true,
    'channel' => 'slack',
    'report_on_status_codes' => [500, 502],
    'channels' => [
        'slack' => ['webhook_url' => env('SLACK_WEBHOOK_URL')],
    ],
],
```

## Configuration

Publish and review the full configuration:

```bash
php artisan vendor:publish --tag=api-kit-config
```

Key configuration options:

| Key | Description | Default |
|-----|-------------|---------|
| `resource_driver` | Metadata driver (`config` or `database`) | `config` |
| `pagination.default_records_per_page` | Default page size | `10` |
| `api_key.enabled` | Enable API key authentication | `false` |
| `api_key.header` | HTTP header for API keys | `X-Authorization` |
| `error_notifications.enabled` | Enable error notifications | `false` |
| `error_notifications.channel` | Notification channel | `slack` |
| `exception_handler_namespace` | Custom handler namespace | `null` (uses `App\Exceptions\Handlers`) |

## Artisan Commands

| Command | Description |
|---------|-------------|
| `api-kit:install` | Install and publish package assets |
| `api-kit:make-request {name}` | Generate a request DTO |
| `api-kit:make-resource {name}` | Generate a resource DTO |
| `api-kit:make-handler {name}` | Generate an exception handler |

## AI-Assisted Development

The package publishes a `docs/CLAUDE.md` file to your project. This file teaches AI coding assistants (Claude, Copilot, etc.) how to use every feature of the package correctly. Keep it in your repository for better AI-generated code.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
