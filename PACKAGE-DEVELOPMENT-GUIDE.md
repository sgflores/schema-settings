# Schema Settings - Package Development Guide

> A comprehensive guide to understanding and contributing to the Laravel Schema Settings package.

---

## Table of Contents

1. [Package Overview](#package-overview)
2. [Architecture Decisions](#architecture-decisions)
3. [Development Setup](#development-setup)
4. [Package Structure](#package-structure)
5. [Key Components Deep Dive](#key-components-deep-dive)
6. [Testing Strategy](#testing-strategy)
7. [Adding New Features](#adding-new-features)
8. [Common Development Tasks](#common-development-tasks)
9. [Performance Considerations](#performance-considerations)
10. [Security Considerations](#security-considerations)
11. [Contributing Guidelines](#contributing-guidelines)

---

## Package Overview

### What Problem Does It Solve?

Laravel applications often need to store and manage configuration that:
- Changes at runtime (can't use `config/` files)
- Needs validation
- Requires type safety
- Should be cached
- Needs audit trails
- Can be user-specific or global

**Schema Settings** solves this by providing a schema-driven approach where you define your settings structure once and get validation, caching, type safety, and audit trails automatically.

### Design Philosophy

1. **Schema First**: Define structure explicitly, prevent runtime errors
2. **Convention Over Configuration**: Sensible defaults, minimal setup
3. **Performance**: Cache aggressively, batch operations, single queries
4. **Developer Experience**: Fluent API, helpful error messages, comprehensive docs
5. **Laravel Native**: Uses Laravel conventions and features

---

## Architecture Decisions

### Why Schema-Driven?

**Problem**: Traditional key-value stores lack structure
```php
// No type information, no validation, no defaults
Setting::get('max_users'); // string? int? null?
```

**Solution**: Schema defines structure
```php
// Type-safe, validated, with defaults
ConfigurableItem::make('max_users')
    ->type(TYPE_INTEGER)
    ->default(100)
    ->rules(['min:1', 'max:1000']);
```

### Why Interface-Based Registration?

**Flexibility**: Allows multiple schemas per application
```php
interface ConfigurableInterface {
    public static function getKey(): ?string;
    public static function registerConfigurables(): array;
}
```

Users can create:
- `GlobalSettings` (getKey returns null)
- `UserSettings` (getKey returns User::class)
- `TeamSettings` (getKey returns Team::class)

### Why Eloquent Models?

**Benefits**:
- Leverage Laravel's ORM features
- Automatic timestamps
- Query scopes
- Relationships
- Easy testing

**Models**:
- `Setting`: Stores actual setting values
- `SettingHistory`: Audit trail

### Why Polymorphic Relationships?

Allows settings to belong to any model:
```sql
reference_type: 'App\Models\User'
reference_id: 1
```

Flexible, extensible, follows Laravel conventions.

### Why Custom Exceptions?

**Better error handling**:
- Specific exceptions for specific scenarios
- HTTP status codes for API responses
- Clear error messages
- Easy to catch and handle

```php
try {
    Settings::get('key');
} catch (SettingNotFoundException $e) {
    // Handle specifically
}
```

---

## Development Setup

### Prerequisites

- PHP 8.1+
- Composer
- Laravel 10/11/12

### Local Development

This package is developed within a Laravel monorepo:

```
Laravel-Packager/
├── app/
├── packages/
│   └── sgflores/
│       └── schema-settings/  ← Package here
├── vendor/
└── composer.json
```

### Installing for Development

1. **Clone the repository**
```bash
git clone <repo-url>
cd Laravel-Packager
```

2. **Install dependencies**
```bash
composer install
```

3. **The package is linked via path repository**
```json
// composer.json
"repositories": [
    {
        "type": "path",
        "url": "./packages/sgflores/schema-settings"
    }
]
```

4. **Changes are immediately available** - no need to reinstall

### Running Tests

**All tests**:
```bash
cd packages/sgflores/schema-settings
vendor/bin/phpunit
```

**Specific test**:
```bash
vendor/bin/phpunit --filter=test_can_get_setting
```

**With coverage**:
```bash
vendor/bin/phpunit --coverage-html coverage
```

---

## Package Structure

```
packages/sgflores/schema-settings/
├── src/
│   ├── Console/                    # Artisan commands
│   │   ├── ListCommand.php         # List all settings
│   │   ├── GetCommand.php          # Get setting value
│   │   ├── SetCommand.php          # Set setting value
│   │   └── ClearCacheCommand.php   # Clear setting cache
│   ├── Contracts/                  # Interfaces
│   │   ├── ConfigurableInterface.php    # Schema contract
│   │   └── SettingsManagerInterface.php # Manager contract
│   ├── Exceptions/                 # Custom exceptions
│   │   ├── SchemaSettingException.php         # Base exception
│   │   ├── SettingNotFoundException.php       # 404 error
│   │   ├── ReadonlySettingException.php       # 403 error
│   │   ├── InvalidSchemaException.php         # 500 error
│   │   └── InvalidConfigurableException.php   # 500 error
│   ├── Facades/                    # Laravel facades
│   │   └── Settings.php
│   ├── Items/                      # Schema builders
│   │   └── ConfigurableItem.php
│   ├── Manager/                    # Core logic
│   │   └── SettingsManager.php     # The brain of the package
│   ├── Models/                     # Eloquent models
│   │   ├── Setting.php             # Settings storage
│   │   └── SettingHistory.php      # Audit trail
│   ├── Traits/                     # Reusable traits
│   │   └── ConfigurableTrait.php   # For models
│   ├── helpers.php                 # Global helper functions
│   └── SchemaSettingServiceProvider.php  # Package entry point
├── stubs/                          # Publishable files
│   ├── config/
│   │   └── schema-settings.php
│   ├── database/
│   │   ├── 2025_10_13_000000_create_schema_settings_table.php
│   │   └── 2025_10_13_000001_create_schema_settings_history_table.php
│   └── app/
│       ├── GlobalSettings.php
│       └── UserSettings.php
├── tests/                          # Test suite
│   ├── Feature/                    # Integration tests
│   ├── Unit/                       # Unit tests
│   ├── Fixtures/                   # Test helpers
│   └── TestCase.php                # Base test class
├── composer.json
├── phpunit.xml
├── README.md
├── DOCUMENTATION.md
├── HELPERS.md
├── PACKAGE-DEVELOPMENT-GUIDE.md
└── LICENSE
```

---

## Key Components Deep Dive

### 1. SchemaSettingServiceProvider

**Purpose**: Package entry point, registers services with Laravel.

**Key Methods**:

```php
public function register(): void
{
    // Merge default config
    $this->mergeConfigFrom(__DIR__.'/../stubs/config/schema-settings.php', 'schema-settings');
    
    // Register SettingsManager as singleton
    $this->app->singleton('schema-settings', function ($app) {
        return new SettingsManager();
    });
    
    // Bind interface for dependency injection
    $this->app->bind(SettingsManagerInterface::class, function ($app) {
        return $app->make('schema-settings');
    });
}

public function boot(): void
{
    // Publish config
    $this->publishes([...], 'schema-settings-config');
    
    // Publish migrations
    $this->publishes([...], 'schema-settings-migrations');
    
    // Register commands
    if ($this->app->runningInConsole()) {
        $this->commands([...]);
    }
}
```

**Auto-Discovery**:
```json
// composer.json
"extra": {
    "laravel": {
        "providers": [
            "SgFlores\\SchemaSetting\\SchemaSettingServiceProvider"
        ]
    }
}
```

---

### 2. SettingsManager (The Brain)

**Responsibilities**:
- Schema registration
- CRUD operations
- Type casting
- Validation
- Caching
- Audit trails

**Key Implementation Details**:

#### Schema Storage
```php
protected array $schema = [];

public function register(string $class): void
{
    $scopeKey = $class::getKey() ?? 'global';
    
    foreach ($class::registerConfigurables() as $item) {
        $item->validate(); // Early validation
        $this->schema[$scopeKey][$item->key] = $item;
    }
}
```

#### Type Casting
```php
protected function castValue(mixed $value, array $config): mixed
{
    return match ($config['type']) {
        ConfigurableItem::TYPE_STRING => (string) $value,
        ConfigurableItem::TYPE_INTEGER => (int) $value,
        ConfigurableItem::TYPE_BOOLEAN => (bool) $value,
        ConfigurableItem::TYPE_FLOAT => (float) $value,
        ConfigurableItem::TYPE_ARRAY, ConfigurableItem::TYPE_JSON => (array) $value,
        ConfigurableItem::TYPE_DATETIME => $this->castToDateTime($value, $config),
        ConfigurableItem::TYPE_ENUM => $this->castToEnum($value, $config),
        default => (string) $value,
    };
}
```

#### Batch Optimization
```php
public function getMultiple(array $keys, ?object $model = null): array
{
    // Check cache first for each key
    $cached = [];
    $missing = [];
    
    foreach ($keys as $key) {
        if ($this->cacheEnabled) {
            $cached[$key] = $this->cache()->get($cacheKey);
            if (!isset($cached[$key])) {
                $missing[] = $key;
            }
        }
    }
    
    // Single query for all missing keys
    if (!empty($missing)) {
        $settings = Setting::query()
            ->whereIn('key', $missing)
            // ... scope conditions
            ->get();
    }
    
    return array_merge($cached, $fromDb);
}
```

---

### 3. ConfigurableItem (Fluent Builder)

**Pattern**: Fluent Builder Pattern

**Implementation**:
```php
public static function make(string $key): static
{
    $instance = new static;
    $instance->key = $key;
    return $instance;
}

public function type(string $type): static
{
    $this->type = $type;
    return $this;
}

// All methods return $this for chaining
```

**Validation Logic**:
```php
public function validate(): void
{
    if (empty($this->type)) {
        throw new InvalidSchemaException("Type must be set");
    }
    
    if ($this->type === self::TYPE_ENUM && !$this->enumClass) {
        throw new InvalidSchemaException("Enum type requires enumClass");
    }
    
    if ($this->default !== null) {
        $this->validateDefaultType();
    }
}
```

---

### 4. Models

#### Setting Model

**Query Scopes**:
```php
public function scopeGlobal($query)
{
    return $query->whereNull('reference_type')
                 ->whereNull('reference_id');
}

public function scopeForModel($query, object $model)
{
    return $query->where('reference_type', $model::class)
                 ->where('reference_id', $model->getKey());
}

public function scopeKey($query, string $key)
{
    return $query->where('key', $key);
}
```

**Usage**:
```php
// Get global setting
Setting::global()->key('site_name')->first();

// Get user-specific setting
Setting::forModel($user)->key('theme')->first();
```

---

## Testing Strategy

### Test Structure

```
tests/
├── Feature/                       # Integration tests
│   ├── SettingsManagerTest.php   # Core functionality
│   ├── ModelScopedSettingsTest.php
│   ├── CachingTest.php
│   ├── ValidationTest.php
│   ├── EncryptionTest.php
│   ├── AuditTrailTest.php
│   ├── RegistrationTest.php
│   ├── ConsoleCommandsTest.php
│   ├── HelperFunctionsTest.php
│   └── InterfaceBindingTest.php
├── Unit/                          # Unit tests
│   ├── ConfigurableItemTest.php
│   ├── ExceptionsTest.php
│   └── TypeCastingTest.php
├── Fixtures/                      # Test helpers
│   ├── TestUser.php
│   ├── TestTeam.php
│   ├── TestGlobalSettings.php
│   └── TestUserSettings.php
└── TestCase.php                   # Base test class
```

### TestCase Setup

```php
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SchemaSettingServiceProvider::class];
    }
    
    protected function getEnvironmentSetUp($app): void
    {
        // Setup database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        
        // Setup encryption key
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../stubs/database');
        $this->artisan('migrate', ['--database' => 'testing']);
    }
}
```

### Test Coverage Goals

- **Feature Tests**: Test complete workflows
- **Unit Tests**: Test individual components
- **Edge Cases**: Test error conditions
- **Performance**: Test batch operations

### Running Tests

```bash
# All tests
vendor/bin/phpunit

# Specific suite
vendor/bin/phpunit --testsuite Feature
vendor/bin/phpunit --testsuite Unit

# Specific test
vendor/bin/phpunit --filter test_can_get_setting

# With coverage
vendor/bin/phpunit --coverage-html coverage
```

---

## Adding New Features

### Example: Adding a New Data Type

**Step 1: Add type constant**
```php
// src/Items/ConfigurableItem.php
class ConfigurableItem
{
    public const TYPE_URL = 'url';
    
    public const ALLOWED_TYPES = [
        // ... existing types
        self::TYPE_URL,
    ];
}
```

**Step 2: Add casting logic**
```php
// src/Manager/SettingsManager.php
protected function castValue(mixed $value, array $config): mixed
{
    return match ($config['type']) {
        // ... existing types
        ConfigurableItem::TYPE_URL => $this->castToUrl($value, $config),
        default => (string) $value,
    };
}

protected function castToUrl(mixed $value, array $config): ?string
{
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        return (string) $value;
    }
    return $config['default'] ?? null;
}
```

**Step 3: Add tests**
```php
// tests/Unit/TypeCastingTest.php
#[Test]
public function it_casts_to_url(): void
{
    $item = ConfigurableItem::make('website')
        ->type(ConfigurableItem::TYPE_URL)
        ->default('https://example.com');
        
    $value = $this->manager->get('website');
    
    $this->assertIsString($value);
    $this->assertTrue(filter_var($value, FILTER_VALIDATE_URL) !== false);
}
```

**Step 4: Update documentation**
- Update README.md with usage examples
- Update DOCUMENTATION.md with technical details
- Update HELPERS.md if adding new helper functions

---

## Common Development Tasks

### Adding a New Artisan Command

**Step 1: Create command class**
```php
// src/Console/ExampleCommand.php
namespace SgFlores\SchemaSetting\Console;

use Illuminate\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'settings:example {key}';
    protected $description = 'Example command';
    
    public function handle(): int
    {
        // Implementation
        return self::SUCCESS;
    }
}
```

**Step 2: Register in ServiceProvider**
```php
// src/SchemaSettingServiceProvider.php
public function boot(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            // ... existing commands
            ExampleCommand::class,
        ]);
    }
}
```

**Step 3: Test the command**
```php
// tests/Feature/ConsoleCommandsTest.php
#[Test]
public function it_runs_example_command(): void
{
    $this->artisan('settings:example', ['key' => 'test'])
         ->assertSuccessful()
         ->expectsOutput('Expected output');
}
```

---

### Adding a New Helper Function

**Step 1: Add to helpers.php**
```php
// src/helpers.php
if (!function_exists('example_helper')) {
    function example_helper(string $key): mixed
    {
        return app('schema-settings')->get($key);
    }
}
```

**Step 2: Test the helper**
```php
// tests/Feature/HelperFunctionsTest.php
#[Test]
public function it_provides_example_helper(): void
{
    $this->assertTrue(function_exists('example_helper'));
    
    $value = example_helper('test_key');
    $this->assertEquals('expected', $value);
}
```

---

## Performance Considerations

### 1. Schema in Memory

**Why**: Schema lookups are frequent
**How**: Store compiled schema in `protected array $schema`
**Benefit**: No database queries for schema validation

### 2. Single Query Batches

**Why**: Reduce database round trips
**Implementation**:
```php
// Instead of:
foreach ($keys as $key) {
    $values[] = Settings::get($key); // N queries
}

// We do:
Setting::whereIn('key', $keys)->get(); // 1 query
```

### 3. Cache Keys

**Why**: Fast lookups, easy invalidation
**Format**: `{prefix}{scope}:{reference_id}:{key}`
**Benefit**: Readable, debuggable, selective clearing

### 4. Transaction-Wrapped Bulk Updates

**Why**: Atomic operations, faster commits
**Implementation**:
```php
DB::transaction(function () use ($settings) {
    foreach ($settings as $key => $value) {
        $this->set($key, $value);
    }
});
```

---

## Security Considerations

### 1. Encryption

**When to use**: API keys, secrets, tokens
**Implementation**: Uses Laravel's `Crypt` facade
**Key**: Requires secure `APP_KEY` in `.env`

### 2. Validation

**Always validate**: Never trust user input
**Laravel rules**: Leverage built-in validation
**Custom rules**: Add your own via schema

### 3. Readonly Settings

**Prevent modification**: System-critical settings
**Example**: Installation date, license key

### 4. SQL Injection

**Protected by**: Eloquent ORM
**Parameterized queries**: All queries use bindings

---

## Contributing Guidelines

### Code Style

**PSR-12**: Follow PHP standards
**Laravel Conventions**: Follow Laravel best practices

### Commit Messages

**Format**: `type: description`

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `test`: Adding tests
- `refactor`: Code refactoring
- `perf`: Performance improvement

**Examples**:
```
feat: add DateTime type support
fix: correct cache invalidation logic
docs: update README examples
test: add encryption tests
```

### Pull Request Process

1. Fork the repository
2. Create feature branch (`git checkout -b feat/amazing-feature`)
3. Make changes
4. Add tests
5. Run test suite (`vendor/bin/phpunit`)
6. Commit changes
7. Push to branch
8. Open pull request

### Testing Requirements

- All new features must have tests
- Maintain or improve coverage
- Tests must pass before merge

---

## Troubleshooting Development Issues

### Tests Failing

**Check**:
- Is database migrated in test environment?
- Are fixtures loaded properly?
- Is `APP_KEY` set in test environment?

### Cache Issues

**Solution**:
```bash
php artisan config:clear
php artisan cache:clear
```

### Autoload Issues

**Solution**:
```bash
composer dump-autoload
```

### Package Not Found

**Check**:
- Is package in `composer.json` repositories?
- Did you run `composer install`?
- Is auto-discovery configured?

---

## Summary

This package provides a robust, performant, type-safe solution for managing application settings. Key architectural decisions prioritize developer experience, performance, and security while maintaining Laravel conventions.

For questions or contributions, please open an issue or pull request on GitHub.
