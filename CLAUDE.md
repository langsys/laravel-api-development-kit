# CLAUDE.md — Package Development Guide

This is the `langsys/laravel-api-development-kit` package. It provides reusable API development patterns for Laravel applications.

## Package Structure

- `src/` — Package source code (namespace: `Langsys\ApiKit`)
- `config/` — Publishable configuration
- `database/migrations/` — Publishable migrations (for DB driver)
- `stubs/` — Publishable stub files for code generation
- `docs/` — Publishable documentation (CLAUDE.md template, conventions)
- `tests/` — PHPUnit tests with Orchestra Testbench

## Key Architecture Decisions

- **Dual-driver resource metadata**: Config driver (zero dependencies) and Database driver (publishable migrations). The `ResourceMetadataResolver` interface abstracts this.
- **Interface-based authorization**: `Authorizable`, `AuthorizableByKey`, `AuthorizableByUser`, `GuardableResource` — consumers implement these on their models.
- **Multi-channel error notifications**: `ErrorNotificationChannel` interface with Slack, Mattermost, Teams, Telegram implementations.
- **Exception handler factory**: Convention-based resolution — checks consumer app namespace first, then package defaults.

## Testing

```bash
composer test
```

Tests use Orchestra Testbench. No SQLite hacks — test actual package behavior.

## Dependencies

- `spatie/laravel-data` ^3.0|^4.0
- `laravel/framework` ^10.0|^11.0|^12.0
