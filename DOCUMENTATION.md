# Schema Settings - Complete Documentation

This document explains how the Laravel Schema Settings package works from a high-level architectural perspective. It's designed for developers who want to understand the package's internals and design decisions.

## Table of Contents

1. [Overview](#overview)
2. [The Problem It Solves](#the-problem-it-solves)
3. [Architecture](#architecture)
4. [Core Components](#core-components)
5. [Data Flow](#data-flow)
6. [Schema Registration](#schema-registration)
7. [Type System](#type-system)
8. [Validation](#validation)
9. [Caching Strategy](#caching-strategy)
10. [Encryption](#encryption)
11. [Audit Trail](#audit-trail)
12. [Model Scoping](#model-scoping)
13. [Performance Optimizations](#performance-optimizations)
14. [Error Handling](#error-handling)
15. [Best Practices](#best-practices)

---

## Overview

**Schema Settings** is a Laravel package that provides a type-safe, schema-driven approach to managing application configuration. Instead of storing settings as simple key-value pairs, you define a schema that specifies types, defaults, validation rules, and metadata for each setting.

### Philosophy

- **Schema First**: Define your settings structure explicitly
- **Type Safety**: Automatic casting to proper PHP types
- **Validation**: Enforce data integrity at the application level
- **Audit Everything**: Track all changes with user attribution
- **Performance**: Cache aggressively, invalidate smartly

---

## The Problem It Solves

### Before Schema Settings

```php
// No type safety
$maintenanceMode = Cache::get('maintenance_mode'); // string? bool? null?

// No validation
Setting::set('max_users', 'not a number'); // Silently fails later

// Manual caching
$value = Cache::remember('setting.site_name', 3600, function() {
    return DB::table('settings')->where('key', 'site_name')->value('value');
});

// No audit trail
// Who changed it? When? From what value?

// Inconsistent access
Setting::get('key1');
config('key2');
env('KEY3');
```

### With Schema Settings

```php
// Type-safe
$maintenanceMode = Settings::get('maintenance_mode'); // Always bool

// Validated
Settings::set('max_users', 'not a number'); // Throws ValidationException

// Auto-cached
$value = Settings::get('site_name'); // Cached automatically

// Full audit trail
SettingHistory::key('site_name')->get(); // See all changes

// Consistent access
Settings::get('anything');
```

---

## Architecture

### High-Level Design

```
┌─────────────────────────────────────────────────────────┐
│                    Application Layer                     │
├─────────────────────────────────────────────────────────┤
│  Facades/Helpers  │  Controllers  │  Models w/ Trait    │
└──────────┬──────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────┐
│                   SettingsManager                        │
│  - Schema Registration                                   │
│  - CRUD Operations                                       │
│  - Validation & Type Casting                            │
│  - Cache Management                                      │
└──────────┬──────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────┬──────────────────────────────┐
│    Cache Layer           │     Database Layer           │
│  - Fast retrieval        │  - Setting (Eloquent)        │
│  - Automatic invalidation│  - SettingHistory (Eloquent) │
└──────────────────────────┴──────────────────────────────┘
```

### Package Structure

```
src/
├── Console/               # Artisan commands
│   ├── ListCommand.php
│   ├── GetCommand.php
│   ├── SetCommand.php
│   └── ClearCacheCommand.php
├── Contracts/             # Interfaces
│   ├── ConfigurableInterface.php
│   └── SettingsManagerInterface.php
├── Exceptions/            # Custom exceptions
│   ├── SchemaSettingException.php
│   ├── SettingNotFoundException.php
│   ├── ReadonlySettingException.php
│   ├── InvalidSchemaException.php
│   └── InvalidConfigurableException.php
├── Facades/               # Laravel Facades
│   └── Settings.php
├── Items/                 # Schema builders
│   └── ConfigurableItem.php
├── Manager/               # Core logic
│   └── SettingsManager.php
├── Models/                # Eloquent models
│   ├── Setting.php
│   └── SettingHistory.php
├── Traits/                # Model traits
│   └── ConfigurableTrait.php
├── helpers.php            # Global helpers
└── SchemaSettingServiceProvider.php
```

---

## Core Components

### 1. ConfigurableInterface

**Purpose**: Contract for defining setting schemas.

**Responsibilities**:
- Define scope (global or model-specific)
- Return array of ConfigurableItem instances

**Implementation**:
```php
interface ConfigurableInterface
{
    public static function getKey(): ?string;
    public static function registerConfigurables(): array;
}
```

---

### 2. ConfigurableItem

**Purpose**: Fluent builder for defining individual settings.

**Responsibilities**:
- Define setting metadata (type, default, rules, etc.)
- Validate schema definition
- Provide fluent API for schema building

**Key Methods**:
```php
ConfigurableItem::make('key')
    ->type(TYPE_STRING)
    ->default('value')
    ->rules(['required', 'max:255'])
    ->group('category')
    ->label('Display Name')
    ->description('Helpful text')
    ->encrypted()
    ->readonly()
    ->options(['a', 'b'])
    ->enum(EnumClass::class)
```

**Available Types**:
- `TYPE_STRING`: String values
- `TYPE_INTEGER`: Integer numbers
- `TYPE_BOOLEAN`: True/false
- `TYPE_FLOAT`: Floating point numbers
- `TYPE_ARRAY`: Simple arrays
- `TYPE_JSON`: Associative arrays
- `TYPE_DATETIME`: DateTime objects
- `TYPE_ENUM`: PHP 8.1+ Enums

---

### 3. SettingsManager

**Purpose**: Core service that handles all setting operations.

**Responsibilities**:
- Schema registration and validation
- CRUD operations (get, set, delete)
- Type casting and validation
- Cache management
- Audit trail recording
- Batch operations

**Key Methods**:

```php
use Illuminate\Database\Eloquent\Model;

// Schema Management
register(string $class): void

// Returns array<string, ConfigurableItem> for single scope
// or array<string, array<string, ConfigurableItem>> for all scopes
getSchema(?string $scopeKey = null): array

has(string $key, ?Model $model = null): bool

// CRUD Operations
get(string $key, ?Model $model = null): mixed
getOrFail(string $key, ?Model $model = null): mixed
set(string $key, mixed $value, ?Model $model = null): bool
delete(string $key, ?Model $model = null): bool

// Batch Operations
getMultiple(array $keys, ?Model $model = null): array<string, mixed>
setMultiple(array $settings, ?Model $model = null): bool
all(?Model $model = null): array<string, mixed>

// Cache Management
clearCache(?string $scopeKey = null, ?int $referenceId = null): void
```

---

### 4. Setting Model

**Purpose**: Eloquent model for the `schema_settings` table.

**Schema**:
```php
id               bigint primary key
key              string (setting identifier)
value            text (JSON-encoded, possibly encrypted)
reference_type   string nullable (polymorphic type)
reference_id     bigint nullable (polymorphic ID)
created_at       timestamp
updated_at       timestamp
```

**Relationships**:
- `reference()`: MorphTo - The model this setting belongs to

**Query Scopes**:
- `global()`: Filter to global settings
- `forModel($model)`: Filter to specific model instance
- `key($key)`: Filter by setting key

---

### 5. SettingHistory Model

**Purpose**: Audit trail for all setting changes.

**Schema**:
```php
id               bigint primary key
key              string
old_value        text nullable
new_value        text nullable
reference_type   string nullable
reference_id     bigint nullable
user_type        string nullable
user_id          bigint nullable
action           string (created/updated/deleted)
created_at       timestamp
```

**Tracking**:
- What changed (key, old/new values)
- Where (scope and reference)
- Who (authenticated user)
- When (timestamp)
- Type of change (action)

---

### 6. ConfigurableTrait

**Purpose**: Convenient model methods for settings.

**Usage**:
```php
class User extends Model
{
    use ConfigurableTrait;
}

$user->setting('theme');
$user->setSetting('theme', 'dark');
$user->settings(['theme', 'language']);
$user->allSettings();
```

---

### 7. Settings Facade

**Purpose**: Static-like access to SettingsManager.

**Usage**:
```php
use SgFlores\SchemaSetting\Facades\Settings;

Settings::get('key');
Settings::set('key', 'value');
```

---

## Data Flow

### Setting Retrieval Flow

```
User Request
    ↓
Settings::get('key', $model)
    ↓
SettingsManager::get()
    ↓
1. Validate key exists in schema
    ↓
2. Generate cache key
    ↓
3. Check cache
    ├── Cache Hit → Return cached value
    └── Cache Miss
        ↓
    4. Query database
        ↓
    5. Deserialize value (decrypt if needed)
        ↓
    6. Cast to proper type
        ↓
    7. Store in cache
        ↓
    8. Return value
```

### Setting Update Flow

```
User Request
    ↓
Settings::set('key', $value, $model)
    ↓
SettingsManager::set()
    ↓
1. Validate key exists in schema
    ↓
2. Check if readonly
    ├── Yes → Throw ReadonlySettingException
    └── No → Continue
        ↓
3. Validate value against rules
    ├── Fails → Throw ValidationException
    └── Passes → Continue
        ↓
4. Serialize value (encrypt if needed)
    ↓
5. Save to database (upsert)
    ↓
6. Invalidate cache
    ↓
7. Record audit trail
    ↓
8. Return success
```

---

## Schema Registration

### Registration Process

```php
// In your AppServiceProvider or dedicated provider
use SgFlores\SchemaSetting\Facades\Settings;
use App\Settings\GlobalSettings;

public function boot(): void
{
    Settings::register(GlobalSettings::class);
}
```

**What Happens**:
1. Validates class implements `ConfigurableInterface`
2. Calls `getKey()` to determine scope
3. Calls `registerConfigurables()` to get schema
4. Validates each `ConfigurableItem`
5. Stores schema in memory for fast lookups

### Global vs Model-Scoped

**Global Settings** (`getKey()` returns `null`):
```php
public static function getKey(): ?string
{
    return null;
}
```

**Model-Scoped Settings** (`getKey()` returns model class):
```php
public static function getKey(): ?string
{
    return \App\Models\User::class;
}
```

---

## Type System

### Type Casting

All values are automatically cast to their declared type:

```php
// String
ConfigurableItem::make('name')->type(TYPE_STRING);
// Stored: "Hello"
// Retrieved: (string) "Hello"

// Integer
ConfigurableItem::make('count')->type(TYPE_INTEGER);
// Stored: "42"
// Retrieved: (int) 42

// Boolean
ConfigurableItem::make('active')->type(TYPE_BOOLEAN);
// Stored: "true"
// Retrieved: (bool) true

// Array
ConfigurableItem::make('items')->type(TYPE_ARRAY);
// Stored: "[\"a\",\"b\"]"
// Retrieved: (array) ["a", "b"]

// DateTime
ConfigurableItem::make('date')->type(TYPE_DATETIME);
// Stored: "2024-01-15 10:30:00"
// Retrieved: (DateTime) DateTime object

// Enum
ConfigurableItem::make('status')->enum(Status::class);
// Stored: "active"
// Retrieved: (Status) Status::Active
```

### Type Safety Benefits

1. **Predictable Returns**: Always get the expected type
2. **No Manual Casting**: Automatic conversion
3. **Error Prevention**: Invalid types caught early
4. **IDE Support**: Better autocomplete and type hints

---

## Validation

### Laravel Validation Integration

```php
ConfigurableItem::make('email')
    ->type(TYPE_STRING)
    ->rules(['required', 'email', 'max:255']);

// Setting invalid value throws ValidationException
Settings::set('email', 'not-an-email'); // ❌ Exception
Settings::set('email', 'user@example.com'); // ✅ Success
```

### Available Validation Rules

All Laravel validation rules are supported:
- `required`, `nullable`
- `string`, `integer`, `boolean`, `numeric`
- `email`, `url`, `ip`, `uuid`
- `min:X`, `max:X`, `between:X,Y`
- `in:a,b,c`, `regex:pattern`
- `date`, `after:date`, `before:date`
- And all other Laravel rules

### Options Validation

```php
ConfigurableItem::make('theme')
    ->options(['light', 'dark', 'auto']);
// Automatically adds 'in:light,dark,auto' rule

Settings::set('theme', 'custom'); // ❌ ValidationException
Settings::set('theme', 'dark'); // ✅ Success
```

---

## Caching Strategy

### Cache Configuration

```php
// config/schema-settings.php
'cache' => [
    'enabled' => true,
    'store' => null, // Use default cache store
    'prefix' => 'schema_settings_',
    'ttl' => 3600, // 1 hour
],
```

### Cache Keys

Format: `{prefix}{scope}:{reference_id}:{key}`

Examples:
- Global: `schema_settings_global:null:site_name`
- User 1: `schema_settings_App\Models\User:1:theme`
- User 2: `schema_settings_App\Models\User:2:theme`

### Cache Invalidation

**Automatic invalidation on**:
- `Settings::set()` - Invalidates specific key
- `Settings::delete()` - Invalidates specific key
- `Settings::setMultiple()` - Invalidates all affected keys

**Manual cache clearing**:
```php
// Clear specific scope
Settings::clearCache('global');

// Clear specific model
Settings::clearCache(User::class, 1);

// Clear all caches
Settings::clearCache();
```

### Performance Impact

**Without caching**:
- Every `get()` = 1 database query
- 100 settings = 100 queries
- Response time: ~500ms

**With caching**:
- First `get()` = 1 database query + cache store
- Subsequent `get()` = Cache retrieval only
- 100 cached settings = 0 queries
- Response time: ~50ms (10x faster)

---

## Encryption

### Encrypted Settings

```php
ConfigurableItem::make('api_key')
    ->type(TYPE_STRING)
    ->encrypted(); // Automatically encrypted
```

**Process**:
1. **On Set**: Value is encrypted using Laravel's `Crypt::encrypt()`
2. **In Database**: Stored as encrypted string
3. **On Get**: Automatically decrypted using `Crypt::decrypt()`
4. **Returned**: Plain text value

**Use Cases**:
- API keys
- Secret tokens
- Passwords (use hashing instead when possible)
- Sensitive configuration

**Security Notes**:
- Uses `APP_KEY` from `.env`
- Requires secure `APP_KEY` management
- Encrypted in database, decrypted in cache (consider implications)

---

## Audit Trail

### Tracking Changes

All setting changes are automatically recorded:

```php
Settings::set('site_name', 'New Name');

// Creates history record:
SettingHistory:
- key: 'site_name'
- old_value: 'Old Name'
- new_value: 'New Name'
- user_id: 1 (authenticated user)
- action: 'updated'
- created_at: '2024-01-15 10:30:00'
```

### Querying History

```php
// Get all changes for a setting
$history = SettingHistory::key('site_name')->get();

// Filter by action
$creates = SettingHistory::action('created')->get();
$updates = SettingHistory::action('updated')->get();
$deletes = SettingHistory::action('deleted')->get();

// Latest change
$latest = SettingHistory::key('site_name')->latest()->first();
```

### Configuration

```php
// config/schema-settings.php
'audit' => [
    'enabled' => true,
    'table_name' => 'schema_settings_history',
],
```

Disable auditing in `config` if not needed for performance.

---

## Model Scoping

### Polymorphic Relationships

Settings can be scoped to any Eloquent model:

```php
// Define User-specific settings
class UserSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return \App\Models\User::class;
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('theme')
                ->type(TYPE_STRING)
                ->default('light'),
        ];
    }
}
```

### Using Model-Scoped Settings

**Via Facade**:
```php
$user = User::find(1);
Settings::get('theme', $user); // User 1's theme
Settings::set('theme', 'dark', $user);
```

**Via Trait**:
```php
$user->setting('theme'); // Cleaner
$user->setSetting('theme', 'dark');
```

### Database Storage

Model-scoped settings use polymorphic relationships:

```sql
-- Global setting
reference_type: NULL
reference_id: NULL

-- User 1 setting
reference_type: 'App\Models\User'
reference_id: 1

-- User 2 setting
reference_type: 'App\Models\User'
reference_id: 2
```

---

## Performance Optimizations

### 1. Batch Operations

**Instead of N queries**:
```php
foreach (['key1', 'key2', 'key3'] as $key) {
    $values[] = Settings::get($key); // 3 queries
}
```

**Use single query**:
```php
$values = Settings::getMultiple(['key1', 'key2', 'key3']); // 1 query
```

### 2. Transaction-Wrapped Bulk Updates

```php
Settings::setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
]); // All-or-nothing, atomic update
```

### 3. Schema Stored in Memory

Schema is registered once and stored in PHP memory, not re-queried from database.

### 4. Optimized Cache Keys

Readable cache keys enable:
- Easy debugging
- Manual cache inspection
- Selective clearing

---

## Error Handling

### Custom Exceptions

All exceptions extend `SchemaSettingException`:

```php
try {
    Settings::get('nonexistent');
} catch (SettingNotFoundException $e) {
    // Handle missing setting
    // $e->getStatusCode() === 404
}

try {
    Settings::set('readonly_setting', 'value');
} catch (ReadonlySettingException $e) {
    // Handle readonly violation
    // $e->getStatusCode() === 403
}

try {
    Settings::set('email', 'invalid');
} catch (ValidationException $e) {
    // Handle validation error
    // Laravel's standard validation exception
}
```

### Exception Hierarchy

```
SchemaSettingException (base)
├── SettingNotFoundException (404)
├── ReadonlySettingException (403)
├── InvalidSchemaException (500)
└── InvalidConfigurableException (500)
```

### HTTP Status Codes

Each exception provides appropriate HTTP status codes for API responses via `getStatusCode()`.

---

## Best Practices

### 1. Schema Organization

**Group related settings**:
```php
// Good
ConfigurableItem::make('smtp_host')->group('email');
ConfigurableItem::make('smtp_port')->group('email');
ConfigurableItem::make('site_name')->group('general');

// Bad - no grouping
ConfigurableItem::make('smtp_host');
ConfigurableItem::make('smtp_port');
```

### 2. Use Labels and Descriptions

```php
ConfigurableItem::make('max_upload_size')
    ->label('Maximum Upload Size')
    ->description('Maximum file size in megabytes for user uploads');
```

### 3. Appropriate Defaults

```php
// Good - sensible defaults
->default(true)
->default(100)
->default('UTC')

// Bad - no default or nonsensical
->default(null) // Requires users to always set
```

### 4. Validate Everything

```php
// Good - strict validation
ConfigurableItem::make('port')
    ->type(TYPE_INTEGER)
    ->rules(['required', 'integer', 'min:1', 'max:65535']);

// Bad - no validation
ConfigurableItem::make('port')
    ->type(TYPE_INTEGER);
```

### 5. Use Readonly for System Settings

```php
ConfigurableItem::make('installation_date')
    ->type(TYPE_DATETIME)
    ->readonly(); // Prevents accidental modification
```

### 6. Encrypt Sensitive Data

```php
ConfigurableItem::make('payment_api_secret')
    ->type(TYPE_STRING)
    ->encrypted();
```

### 7. Batch Operations

```php
// Good - single query
$settings = Settings::getMultiple(['key1', 'key2', 'key3']);

// Bad - multiple queries
$val1 = Settings::get('key1');
$val2 = Settings::get('key2');
$val3 = Settings::get('key3');
```

### 8. Use Enums for Fixed Options

```php
// Good - type-safe
enum Theme: string {
    case Light = 'light';
    case Dark = 'dark';
}

ConfigurableItem::make('theme')->enum(Theme::class);

// Okay - string with options
ConfigurableItem::make('theme')->options(['light', 'dark']);
```

---

## Summary

**Schema Settings** provides:
- ✅ Type-safe configuration management
- ✅ Automatic validation and casting
- ✅ Built-in caching for performance
- ✅ Encryption for sensitive data
- ✅ Complete audit trail
- ✅ Global and model-scoped settings
- ✅ Developer-friendly API

By defining your settings schema once, you get all these features automatically for every setting in your application.
