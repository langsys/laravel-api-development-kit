# CLAUDE.md — API Development Kit Patterns

This project uses `langsys/laravel-api-development-kit` for structured API development. Follow these patterns when working with this codebase.

## DTO System

### Request DTOs
Request DTOs extend `Langsys\ApiKit\Data\BaseData` (which extends Spatie `Data`).

- All constructor properties should be nullable for PATCH support
- Use `$dto->filled()` to get only non-null values for database updates
- Use `$dto->filled(['extra' => 'value'])` to merge additional data
- Use `$dto->filled([], appendNullsFromRequest: true)` when explicit nulls should clear fields

```php
class UpdateUserRequest extends BaseData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
    ) {}
}

// In controller:
$user->update($data->filled());
```

### Resource DTOs
Resource DTOs also extend `BaseData` and define API response shapes.

### Internal DTOs
For non-request data objects, extend `Langsys\ApiKit\Data\BaseInternalData`. No Spatie dependency — provides `toArray()` and `toJson()`.

## Custom Attributes

### #[CastInput]
Applied to DTO properties to cast input values to the property's type before validation:
```php
#[\Langsys\ApiKit\Attributes\CastInput]
public ?int $quantity = null;
```

### #[BindTo]
Auto-loads an Eloquent model by the property value (typically an ID):
```php
#[\Langsys\ApiKit\Attributes\BindTo(\App\Models\Project::class)]
public ?string $project_id = null;
```
The model is available as a camelCase property (e.g., `$request->attributes->get('_bound_models')['project']`).

## Middleware

Register these middleware in your application:

### RequestDtoProcessor
Auto-discovers DTO classes from controller method type hints. Handles validation, casting, model binding, and nested DTO processing.
```php
// In bootstrap/app.php or Kernel.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(append: [
        \Langsys\ApiKit\Middleware\RequestDtoProcessor::class,
    ]);
})
```

### BooleanQueryParamCast
Casts boolean query string values before Spatie Data processes them (fixes a known issue with query param boolean casting).
```php
\Langsys\ApiKit\Middleware\BooleanQueryParamCast::class
```

### AuthorizeApiKey
Validates API keys from the configured header. Allows Sanctum-authenticated requests through.
```php
\Langsys\ApiKit\Middleware\AuthorizeApiKey::class
```

## Response Patterns

Controllers use the `ApiResponse` trait for consistent responses:

```php
use Langsys\ApiKit\Traits\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;

    // Single resource
    public function show(string $id): JsonResponse
    {
        return $this->resourceResponse(UserResource::from($user));
    }

    // Collection with filtering, ordering, and pagination
    public function index(): JsonResponse
    {
        return $this->resourceListResponse(
            UserResource::collect($users),
            paginated: true,
            ordered: true,
            filtered: true
        );
    }

    // Success with no data
    public function activate(string $id): JsonResponse
    {
        return $this->successResponse();
    }

    // Error
    public function fallback(): JsonResponse
    {
        return $this->errorResponse('Not found', 404);
    }

    // No content (204)
    public function destroy(string $id): JsonResponse
    {
        return $this->noContentResponse();
    }
}
```

### Query Parameters
- `filter_by[]=field:value` — Filter by field value
- `order_by[]=field:direction` — Order by field (asc/desc)
- `page=1` — Page number
- `records_per_page=10` — Items per page

## Exception Handling

### Using the Exception Handler
In your exception handler, delegate to the factory:

```php
use Langsys\ApiKit\Exceptions\ExceptionHandlerFactory;
use Langsys\ApiKit\Traits\ApiResponse;

// In your Handler's render method:
$handler = ExceptionHandlerFactory::resolve($exception);
$result = $handler->handle($request, $exception);
return $this->errorResponse($result->getMessage(), $result->code);
```

### Custom Exception Handlers
Create handlers in `app/Exceptions/Handlers/` following the naming convention `{ExceptionName}Handler`:

```php
class CustomExceptionHandler extends BaseExceptionHandler
{
    protected int $statusCode = HttpCode::BAD_REQUEST->value;

    public function handle(Request $request, Throwable $exception): ExceptionResponse
    {
        return new ExceptionResponse($exception->getMessage(), $this->statusCode);
    }
}
```

The factory checks your app namespace first, then falls back to package defaults.

## Authorization

### AccessGuardService
For role-based + API key authorization:

```php
$guard = app(AccessGuardService::class);
$guard->authorize('manage_users', $organization);

// Filter collection by permission
$accessible = $guard->filterByPermission('view_project', $projects);
```

Your models must implement the authorization interfaces:
- `Authorizable` — on user model (hasPermission, isSuperAdmin)
- `AuthorizableByKey` — on API key model (keyHasPermission, keyBelongsToEntity)
- `AuthorizableByUser` — on user model (userRoleInEntity, roleHasPermission, userHasDisabledEntity)
- `GuardableResource` — on entities (orgs, projects, teams)

## Resource Metadata (Config Driver)

Define filterable/orderable fields in `config/api-kit.php`:

```php
'resources' => [
    'UserResource' => [
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

## Artisan Commands

- `php artisan api-kit:install` — Install and publish package assets
- `php artisan api-kit:make-request {name}` — Generate a request DTO
- `php artisan api-kit:make-resource {name}` — Generate a resource DTO
- `php artisan api-kit:make-handler {name}` — Generate an exception handler

## Conventions

- Private methods: prefix with underscore is optional, be consistent within a file
- Always use `filled()` for updates, never `toArray()` directly
- Use `BaseData` for request/response DTOs, `BaseInternalData` for internal objects
- Define all DTO properties in the constructor (promoted properties)
- Exception handlers follow `{ExceptionClassName}Handler` naming convention
- Use `HttpCode` enum instead of magic numbers for status codes
