# Coding Conventions

These are recommended conventions for projects using the API Development Kit. They are not enforced by the package.

## General

- Use PHP 8.1+ features: enums, readonly properties, constructor promotion, named arguments, match expressions
- Prefer strict types: `declare(strict_types=1);`

## DTOs

- Request DTOs go in `app/Data/Requests/`
- Resource DTOs go in `app/Data/Resources/`
- Internal DTOs go in `app/Data/Internal/`
- All DTO properties use constructor promotion
- Request DTO properties are nullable for PATCH support

## Controllers

- One controller per resource
- Use the `ApiResponse` trait
- Type-hint request DTOs in method signatures for automatic processing
- Return `JsonResponse` from all methods

## Services

- Business logic lives in service classes, not controllers
- Services are stateless — inject dependencies via constructor
- Name pattern: `{Resource}Service` (e.g., `UserService`)

## Exception Handlers

- Place in `app/Exceptions/Handlers/`
- Name pattern: `{ExceptionName}Handler`
- Extend `BaseExceptionHandler`
- Set `$statusCode` as a property, not inline

## Naming

- Variables: `camelCase`
- Methods: `camelCase`
- Classes: `PascalCase`
- Database columns: `snake_case`
- Config keys: `snake_case`
- Enum cases: `UPPER_SNAKE_CASE` for value enums, `PascalCase` for label enums
- Route parameters: `snake_case`

## Null Safety

- Prefer null coalescing (`??`) over ternary
- Use nullable types explicitly (`?string`)
- Check for null before accessing properties on potentially null objects

## Testing

- Test files mirror the source structure
- Use descriptive test method names: `test_user_can_update_their_profile`
- Test both success and failure paths
- Use factories for test data
