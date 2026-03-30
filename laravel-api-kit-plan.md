# Laravel API Development Kit — Implementation Plan

> **Goal**: Create `langsys/laravel-api-development-kit` as a standalone package in a new repo. Extract patterns from the Langsys codebase (`langsys/backend/langsys-nova`), abstract and decouple them, then validate via a fresh test project. Backporting into Langsys is out of scope — that will be a separate effort later.

> **Reference codebase**: The Langsys Nova project at the path where this plan lives. The implementing agent should read the source files listed below to understand each pattern before extracting.

---

## Phase 1: Package Scaffolding

Create a new Composer package with:
- Namespace: `Langsys\ApiKit`
- Laravel auto-discovery via `ApiKitServiceProvider`
- Publishable config: `config/api-kit.php`
- Publishable migrations (optional — for DB-driven filters/ordering and API keys)
- Publishable stubs (example controller, DTOs, CLAUDE.md template)
- Publishable convention guidelines (optional coding style recommendations)
- PHPUnit test suite with Orchestra Testbench

```
langsys/laravel-api-development-kit/
├── src/
│   ├── ApiKitServiceProvider.php
│   ├── Attributes/
│   ├── Data/
│   ├── Traits/
│   ├── Middleware/
│   ├── Exceptions/
│   ├── Notifications/
│   ├── ApiKey/
│   ├── Authorization/
│   ├── Enums/
│   └── Console/
├── config/
│   └── api-kit.php
├── database/migrations/ (publishable)
├── stubs/ (publishable)
├── docs/
│   ├── CLAUDE.md (template for consumer projects)
│   └── conventions.md (optional coding style guide)
├── tests/
├── composer.json
├── phpunit.xml
├── CLAUDE.md (for developing the package itself)
└── README.md
```

---

## Phase 2: Core — DTO System

Extract the DTO base classes and request processing pipeline. This is the foundation everything else builds on.

### 2.1 Base Data Classes
- **Source**: `app/Data/BaseData.php`, `app/Data/Internal/BaseInternalData.php`
- **Target**: `src/Data/BaseData.php`, `src/Data/BaseInternalData.php`
- **Abstraction needed**: Remove any Langsys-specific imports. `BaseData` extends Spatie `Data` — keep that dependency. `BaseInternalData` is standalone (no Spatie dependency).

### 2.2 Request DTO Processor Middleware
- **Source**: `app/Http/Middleware/RequestDtoProcessor.php`
- **Target**: `src/Middleware/RequestDtoProcessor.php`
- **Abstraction needed**: The middleware auto-discovers DTO classes from controller method signatures via reflection. Include all generic capabilities: validation rule extraction, `#[CastInput]`, `#[BindTo]` model binding, nested DTO validation, locale formatting. These attributes are useful and should be part of the package.

### 2.3 Custom Attributes
- **Source**: Check `app/Attributes/` and any attribute classes referenced by `RequestDtoProcessor`
- **Target**: `src/Attributes/`
- **What to include**: All generic validation/casting/binding attributes (`CastInput`, `BindTo`, custom validation attributes like `Min`, `Max`, etc.)
- **What to skip**: Any attributes tied to Langsys business logic (e.g., locale-specific or translation-specific attributes)

### 2.4 Boolean Query Param Cast Middleware
- **Source**: `app/Http/Middleware/BooleanQueryParamCast.php`
- **Target**: `src/Middleware/BooleanQueryParamCast.php`
- **Abstraction needed**: Minimal — this is already generic. Detects boolean properties on DTOs and casts query string values.

### 2.5 Enums
- **Source**: `app/Enums/HttpCode.php`, `app/Enums/Pagination.php` (and any other generic enums used by the extracted code)
- **Target**: `src/Enums/`
- **HttpCode enum**: The Langsys version likely has gaps. Ship a **complete** HTTP status code enum covering all standard codes (1xx through 5xx) per RFC 9110. Include: 100-101, 200-208, 226, 300-308, 400-431, 451, 500-511.
- **Also extract**: `EnumHasValues` trait from `app/Traits/EnumHasValues.php`

---

## Phase 3: Core — Response Pipeline

### 3.1 API Response Trait
- **Source**: `app/Traits/ApiResponse.php`
- **Target**: `src/Traits/ApiResponse.php`
- **Abstraction needed**: This trait composes `OrderableCollections`, `FilterableCollection`, `CollectionPaginator`. All three must be extracted together. Remove any references to Langsys-specific models or services.

### 3.2 Collection Paginator
- **Source**: `app/Traits/CollectionPaginator.php`
- **Target**: `src/Traits/CollectionPaginator.php`
- **Abstraction needed**: Should reference `Pagination` enum from the package, not Langsys.

### 3.3 Orderable Collections
- **Source**: `app/Traits/OrderableCollections.php`
- **Target**: `src/Traits/OrderableCollections.php`
- **Abstraction needed**: Currently reads orderable field config from `ApiResource` model (DB). Must support **both** config-file and DB-driven modes. See Phase 5 for the dual-driver approach.

### 3.4 Filterable Collection
- **Source**: `app/Traits/FilterableCollection.php`
- **Target**: `src/Traits/FilterableCollection.php`
- **Abstraction needed**: Same as orderable — currently DB-driven via `ApiResourceService`. Must support both config-file and DB-driven modes.

---

## Phase 4: Exception Handling

### 4.1 Exception Handler Factory
- **Source**: `app/Exceptions/Handler.php`, `app/Exceptions/BaseExceptionHandler.php`, `app/Exceptions/ExceptionHandlerFactory.php`, `app/Exceptions/Handlers/*.php`
- **Target**: `src/Exceptions/`
- **Abstraction needed**: Extract the factory pattern and base handler. Ship common handlers (validation, auth, not found, model not found, etc.). Make it easy for consumers to add their own handlers by following the naming convention.
- **Also extract**: `ExceptionResponse` DTO from `app/Data/Internal/ExceptionResponse.php`, `ErrorData` from `app/Data/Internal/ErrorData.php`.

### 4.2 Error Notification Service
- **Source**: `app/Services/ErrorService.php`, `app/Jobs/ReportErrorToMattermost.php`
- **Target**: `src/Notifications/ErrorNotificationService.php`, `src/Notifications/Channels/`
- **Abstraction needed**: Major. Replace hardcoded Mattermost with a channel interface:
  ```php
  interface ErrorNotificationChannel {
      public function send(ErrorData $error): void;
  }
  ```
  Ship implementations for: Mattermost, Slack, Microsoft Teams, Telegram.
  Config selects channel + credentials:
  ```php
  // config/api-kit.php
  'error_notifications' => [
      'enabled'  => env('API_KIT_ERROR_NOTIFICATIONS', false),
      'channel'  => env('API_KIT_ERROR_CHANNEL', 'slack'), // slack|mattermost|teams|telegram
      'channels' => [
          'slack'       => ['webhook_url' => env('SLACK_WEBHOOK_URL')],
          'mattermost'  => ['webhook_url' => env('MATTERMOST_WEBHOOK_URL')],
          'teams'       => ['webhook_url' => env('TEAMS_WEBHOOK_URL')],
          'telegram'    => ['bot_token' => env('TELEGRAM_BOT_TOKEN'), 'chat_id' => env('TELEGRAM_CHAT_ID')],
      ],
  ],
  ```
  Keep the deduplication logic (cache-based, configurable TTL).

---

## Phase 5: Resource Metadata (Filters & Ordering Config)

This is the system that tells the response pipeline which fields are filterable/orderable and their types.

### Dual-driver approach
- **Config driver** (default, zero migrations): Define filterable/orderable fields per resource in `config/api-kit.php`:
  ```php
  'resources' => [
      'users' => [
          'filterable' => [
              'status' => ['type' => 'enum', 'enum' => UserStatus::class],
              'role'   => ['type' => 'string'],
          ],
          'orderable'  => ['created_at', 'name', 'email'],
          'default_order' => ['created_at' => 'desc'],
      ],
  ],
  ```
- **Database driver** (publish migrations): Uses `api_resources`, `resource_filterable_fields`, `resource_orderable_fields` tables. Allows runtime changes.
- **Source for DB driver**: `app/Models/ApiResource.php`, `app/Models/ResourceFilterableField.php`, `app/Models/ResourceOrderableField.php`, `app/Services/ApiResourceService.php`
- **Implementation**: Create a `ResourceMetadataResolver` interface with `ConfigResolver` and `DatabaseResolver` implementations. The `FilterableCollection` and `OrderableCollections` traits call the resolver, not the DB directly.

---

## Phase 6: Authentication & Authorization

### 6.1 Auth Data Abstraction
- **Source**: `app/Data/Internal/AuthData.php`, `app/Traits/AuthDataResolver.php`
- **Target**: `src/Data/AuthData.php`, `src/Traits/AuthDataResolver.php`
- **Abstraction needed**: `AuthData` is a singleton-like pattern that gives readily available auth data via a helper method — for systems that support both Sanctum bearer tokens and API keys. The user model is the consumer's — use an interface (`Authenticatable`) so the package doesn't care about the concrete model. The `authData()` helper resolves from request context (reads `Authorization` header + `Auth::id()` for Sanctum, or API key header).

### 6.2 API Key System
- **Source**: `app/Models/ApiKey.php`, `app/Http/Middleware/AuthorizeApiKey.php`, `app/Constants/ApiKeys.php`
- **Target**: `src/ApiKey/`
- **Abstraction needed**:
  - Strip billing/deduction entirely (that's Langsys-specific)
  - Keep: key generation, active/inactive status, permission checking, middleware
  - Ship publishable migration for `api_keys` and `api_key_permissions` tables
  - The `ApiKey` model should be extendable by consumers

### 6.3 Access Guard Service
- **Source**: `app/Services/Authorization/AccessGuardService.php`
- **Target**: `src/Authorization/AccessGuardService.php`
- **Abstraction needed**: Major, but must preserve all current capabilities so Langsys can adopt without losing functionality.

  **Current Langsys behavior to preserve:**
  1. Super admin bypass — certain user types skip all checks
  2. Permission lookup by string value → Permission model
  3. Type-based dispatch — different auth paths for API keys vs users
  4. API key auth: checks key has permission AND entity is linked to that key (`entity->apiKeys()`)
  5. User auth: resolves user's role in the entity (`user->roleInEntity($entity)`), checks role has permission, AND checks user hasn't disabled that entity (project-specific)
  6. Collection filtering: `filterByPermission()` iterates a collection and returns only items the auth entity can access

  **Abstraction approach — use interfaces, not concrete models:**
  ```php
  interface Authorizable {
      public function hasPermission(string $permission): bool;
      public function isSuperAdmin(): bool;
  }

  interface AuthorizableByKey {
      public function keyHasPermission(string $permission): bool;
      public function keyBelongsToEntity(mixed $entity): bool;
  }

  interface AuthorizableByUser {
      public function userRoleInEntity(mixed $entity): ?object;
      public function roleHasPermission(object $role, string $permission): bool;
      public function userHasDisabledEntity(mixed $entity): bool;
  }

  interface GuardableResource {
      // Implemented by entities (org, project, etc.) that can be authorized against
  }
  ```
  The `AccessGuardService` works against these interfaces. Langsys implements them on its models. New consumers implement them for their own hierarchy (could be teams, workspaces, tenants, whatever).

  `filterByPermission()` stays as a generic collection filter method on the service.

---

## Phase 7: Utility Traits

### 7.1 UUID Trait
- **Source**: `app/Traits/Uuid.php`
- **Target**: `src/Traits/Uuid.php`
- **Abstraction needed**: None — already generic.

### 7.2 EnumHasValues Trait
- **Source**: `app/Traits/EnumHasValues.php`
- **Target**: `src/Traits/EnumHasValues.php`
- **Abstraction needed**: None — already generic.

---

## Phase 8: Artisan Commands & Installation

### 8.1 Install Command
`php artisan api-kit:install` — publishes config, optionally publishes migrations, generates CLAUDE.md template, creates example DTO and controller stubs.

### 8.2 Langsys Setup Command (optional)
`php artisan api-kit:langsys` — configures Langsys translation integration (API key, locale settings, translation driver). This is the Langsys promotion hook.

### 8.3 Make Commands
- `php artisan api-kit:make-request {name}` — generates a request DTO extending `BaseData` with validation attributes
- `php artisan api-kit:make-resource {name}` — generates a resource DTO extending `BaseData` with OpenAPI annotations
- `php artisan api-kit:make-handler {name}` — generates an exception handler extending `BaseExceptionHandler`

**Relationship with openapi-docs-generator**: The openapi package has `openapi:dto` which generates a generic Spatie Data DTO from a model's DB schema. That command is model-introspection focused (reads columns → generates properties). The api-kit make commands are different — they generate *role-specific* DTOs (request vs resource) with the correct base class, annotations, and patterns pre-filled. No overlap. The two complement each other: use `openapi:dto` to bootstrap a DTO from a model, then use `api-kit:make-request`/`api-kit:make-resource` to create the properly structured request/response versions.

---

## Phase 9: Documentation

### 9.1 CLAUDE.md Template
Ship a comprehensive CLAUDE.md template that documents all the package's patterns for LLM-assisted development. This is a key differentiator — "AI-ready API development."

### 9.2 README
Standard package docs: installation, configuration, usage examples for each feature.

### 9.3 Stubs with Inline Comments
The publishable stubs (example controller, DTOs) should have clear inline comments explaining the patterns so developers learn by reading.

### 9.4 Convention Guidelines
Ship an optional `conventions.md` that recommends coding style for API projects using the kit. Not enforced — just guidance. Covers: private method naming, string conventions, null safety, variable naming, test conventions, etc. This becomes part of the CLAUDE.md template too, so LLMs follow the style automatically.

---

## Phase 10: Starter Kit (Separate Repo)

After the package is stable, create `langsys/laravel-api-starter`:
- Fresh Laravel project pre-configured with the package
- Example CRUD module (controller + service + DTOs + tests)
- Pre-configured CLAUDE.md
- Pre-configured middleware stack
- Docker setup with PostgreSQL
- CI pipeline template

---

## Scope — What's NOT Included

These were considered and intentionally excluded:
- `PreventDuplicateRequests` middleware — too opinionated, Redis dependency
- `TransactionHandler` middleware — auto-wrapping mutations is too magical for a generic package
- `WithDatabaseTransactions` job trait — same reasoning
- `DeductApiKeyRequest` / billing system — Langsys-specific
- `ActivityHandler` / user activity logging — too coupled to specific user model
- Backporting into Langsys — separate effort, separate agent, after validation

---

## Dependencies

The package will require:
- `php: ^8.1` (floor set by Spatie laravel-data v3)
- `laravel/framework: ^10.0|^11.0|^12.0` (no restrictive pinning — match what Spatie supports)
- `spatie/laravel-data: ^3.0|^4.0`
- `langsys/openapi-docs-generator` (for OpenAPI generation)

Development/testing will target the latest Laravel (12) but the package should not use any Laravel-version-specific APIs unless guarded.

---

## Implementation Notes for the Agent

When implementing this plan in the new repo:

1. **Read the source files listed above** in the Langsys codebase before extracting. Understand how they work together.
2. **Don't copy-paste blindly** — the whole point is abstracting away Langsys-specific coupling. Every `use App\...` import is a red flag that needs to become either a package class or a configurable interface.
3. **Test with Orchestra Testbench** — no SQLite hacks, test the actual package behavior.
4. **Config file should be well-documented** with comments explaining each option and sensible defaults.
5. **The CLAUDE.md template is a first-class deliverable**, not an afterthought. It should teach any LLM how to use every feature of the package correctly.
6. **Convention guidelines are optional** — ship them as a publishable doc, reference them in the CLAUDE.md template, but don't enforce them in the package code.
