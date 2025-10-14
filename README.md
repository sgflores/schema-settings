# Laravel Schema Settings

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com/)

A powerful Laravel package for managing dynamic, schema-driven application settings with validation, caching, encryption, and audit trails.

## What is Schema Settings?

Schema Settings provides a **type-safe, validated approach** to managing application configuration. Define your settings schema once, then get automatic validation, type casting, caching, and audit trails for all setting changes.

### Key Features

- ✅ **Schema-Driven** - Define settings with types, defaults, and validation rules
- ✅ **Type Safety** - 8 data types with automatic casting (string, int, bool, float, array, json, datetime, enum)
- ✅ **Validation** - Laravel validation rules enforced on all changes
- ✅ **Caching** - Automatic caching with smart invalidation
- ✅ **Encryption** - Built-in encryption for sensitive data
- ✅ **Readonly Settings** - Prevent runtime modification of critical settings
- ✅ **Audit Trail** - Track all changes with user attribution
- ✅ **Global & Model-Scoped** - Settings for entire app or specific models
- ✅ **Performance Optimized** - Batch operations use single database queries

## Installation

Install via Composer:

```bash
composer require sgflores/schema-settings
```

## Quick Setup

### 1. Publish Configuration & Migrations

```bash
# Publish config file
php artisan vendor:publish --tag=schema-settings-config

# Publish migrations
php artisan vendor:publish --tag=schema-settings-migrations

# Run migrations
php artisan migrate
```

### 2. Publish Example Schema Classes

```bash
php artisan vendor:publish --tag=schema-settings-configurables
```

This creates example schema classes:
- `app/Settings/GlobalSettings.php` - Application-wide settings
- `app/Settings/UserSettings.php` - User-specific settings

### 3. Define Your Settings Schema

Edit `app/Settings/GlobalSettings.php`:

```php
<?php

namespace App\Settings;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class GlobalSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return null; // null = global settings
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('site_name')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('My Application')
                ->rules(['required', 'string', 'max:255'])
                ->group('general')
                ->label('Site Name'),

            ConfigurableItem::make('maintenance_mode')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(false)
                ->group('general'),
        ];
    }
}
```

### 4. Register Your Schema

In your `AppServiceProvider` (or dedicated provider):

```php
use SgFlores\SchemaSetting\Facades\Settings;
use App\Settings\GlobalSettings;

public function boot(): void
{
    Settings::register(GlobalSettings::class);
}
```

## Basic Usage

### Using the Facade

```php
use SgFlores\SchemaSetting\Facades\Settings;

// Get a setting
$siteName = Settings::get('site_name');

// Set a setting
Settings::set('site_name', 'My Awesome App');

// Get multiple settings
$settings = Settings::getMultiple(['site_name', 'maintenance_mode']);

// Get all settings
$all = Settings::all();
```

### Using Helper Functions

```php
// Get a setting
$siteName = setting('site_name');

// Set a setting
set_setting('site_name', 'My Awesome App');

// Check if setting exists
if (has_setting('site_name')) {
    // ...
}
```

### Model-Scoped Settings

**Define a model-scoped schema:**

```php
// app/Settings/UserSettings.php
class UserSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return \App\Models\User::class; // Scoped to User model
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('theme')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('light')
                ->options(['light', 'dark']),
            
            ConfigurableItem::make('notifications_enabled')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true),
        ];
    }
}
```

**Add the trait to your model:**

```php
use SgFlores\SchemaSetting\Traits\ConfigurableTrait;

class User extends Model
{
    use ConfigurableTrait;
}
```

**Use settings on model instances:**

```php
// Get user-specific setting
$theme = $user->setting('theme');

// Set user-specific setting
$user->setSetting('theme', 'dark');

// Get multiple settings
$preferences = $user->settings(['theme', 'notifications_enabled']);
```

## Available Data Types

| Type | PHP Type | Example |
|------|----------|---------|
| `TYPE_STRING` | string | `'Hello World'` |
| `TYPE_INTEGER` | int | `42` |
| `TYPE_BOOLEAN` | bool | `true` |
| `TYPE_FLOAT` | float | `3.14` |
| `TYPE_ARRAY` | array | `['a', 'b']` |
| `TYPE_JSON` | array | `['key' => 'value']` |
| `TYPE_DATETIME` | DateTime | `new DateTime()` |
| `TYPE_ENUM` | Enum | `Status::Active` |

## Artisan Commands

```bash
# List all registered settings
php artisan settings:list

# Get a setting value
php artisan settings:get site_name

# Set a setting value
php artisan settings:set site_name "My App"

# Clear setting cache
php artisan settings:clear-cache
```

## Documentation

- 📖 **[Complete Documentation](DOCUMENTATION.md)** - How the package works
- 🛠️ **[Helper Functions](HELPERS.md)** - Global helper reference
- 🔧 **[Package Development](PACKAGE-DEVELOPMENT-GUIDE.md)** - For contributors

## Advanced Features

### Validation

All settings are validated using Laravel's validation rules:

```php
ConfigurableItem::make('email')
    ->type(ConfigurableItem::TYPE_STRING)
    ->rules(['required', 'email', 'max:255']);

// This will throw ValidationException
Settings::set('email', 'invalid-email'); // ❌ Validation fails
Settings::set('email', 'user@example.com'); // ✅ Success
```

### Encryption

Sensitive settings are automatically encrypted in the database:

```php
ConfigurableItem::make('api_key')
    ->type(ConfigurableItem::TYPE_STRING)
    ->encrypted(); // Automatically encrypted/decrypted
```

### Readonly Settings

Prevent runtime modification of critical settings:

```php
ConfigurableItem::make('installation_date')
    ->type(ConfigurableItem::TYPE_DATETIME)
    ->readonly(); // Cannot be modified at runtime

// Throws ReadonlySettingException
Settings::set('installation_date', now()); // ❌ Error
```

### Grouped Settings

Organize settings into logical groups:

```php
ConfigurableItem::make('smtp_host')
    ->type(ConfigurableItem::TYPE_STRING)
    ->group('email')
    ->label('SMTP Host')
    ->description('Your SMTP server hostname');
```

### Enum Support (PHP 8.1+)

Type-safe enum support:

```php
enum Status: string {
    case Active = 'active';
    case Inactive = 'inactive';
}

ConfigurableItem::make('status')
    ->enum(Status::class)
    ->default(Status::Active);

// Returns Status enum instance, not string
$status = Settings::get('status'); // Status::Active
```

### Options/Dropdown Values

Restrict values to predefined options:

```php
ConfigurableItem::make('theme')
    ->type(ConfigurableItem::TYPE_STRING)
    ->options(['light', 'dark', 'auto'])
    ->default('light');
```

### Exception Handling

The package uses custom exceptions for better error handling:

```php
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Exceptions\ReadonlySettingException;
use Illuminate\Validation\ValidationException;

try {
    Settings::get('non_existent');
} catch (SettingNotFoundException $e) {
    // Handle missing setting (404)
    return response()->json(['error' => $e->getMessage()], 404);
}

try {
    Settings::set('readonly_field', 'value');
} catch (ReadonlySettingException $e) {
    // Handle readonly violation (403)
    return response()->json(['error' => $e->getMessage()], 403);
}

try {
    Settings::set('email', 'invalid');
} catch (ValidationException $e) {
    // Handle validation errors (422)
    return response()->json(['errors' => $e->errors()], 422);
}
```

### Audit Trail

All setting changes are automatically tracked:

```php
use SgFlores\SchemaSetting\Models\SettingHistory;

// Get history for a setting
$history = SettingHistory::key('site_name')
    ->orderBy('created_at', 'desc')
    ->get();

// Get recent changes
$recent = SettingHistory::orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Each history record includes:
// - old_value, new_value
// - user_id (who made the change)
// - action (created/updated/deleted)
// - created_at (when)
```

## Configuration

The `config/schema-settings.php` file allows you to customize:

```php
return [
    'table_name' => 'schema_settings',
    
    'cache' => [
        'enabled' => true,
        'store' => null,
        'prefix' => 'schema_settings_',
        'ttl' => 3600,
    ],
    
    'audit' => [
        'enabled' => true,
        'table_name' => 'schema_settings_history',
    ],
];
```

## Testing

The package includes a comprehensive, well-organized test suite:

```bash
cd packages/sgflores/schema-settings
composer test
```

**Test Suite Features:**
- ✅ Comprehensive unit and feature tests
- ✅ All 8 data types fully tested
- ✅ PHP 8.1+ enum support (string and integer-backed)
- ✅ DateTime handling with multiple formats
- ✅ Batch operations with performance verification
- ✅ Error recovery and graceful degradation
- ✅ All console commands tested
- ✅ Fast execution with in-memory SQLite
- ✅ Excellent test coverage

**Test Organization:**
- Focused test files for better clarity
- Comprehensive edge case coverage
- Error scenario testing
- Performance optimization validation

See `tests/README.md` for detailed test documentation.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Author

**sgflores**
- Email: floresopic@gmail.com
- GitHub: [@sgflores](https://github.com/sgflores)

---

**Made with ❤️ for the Laravel community**
