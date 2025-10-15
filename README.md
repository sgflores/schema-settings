# Laravel Schema Settings

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com/)

A powerful Laravel package for managing dynamic, schema-driven application settings with validation, caching, encryption, and audit trails.

## What is Schema Settings?

Schema Settings provides a **type-safe, validated approach** to managing application configuration. Define your settings schema once, then get automatic validation, type casting, caching, and audit trails for all setting changes.

### Key Features

- ✅ **Schema-Driven** - Define settings with types, defaults, and validation rules
- ✅ **Type Safety** - 8 data types with automatic casting
- ✅ **Enhanced Validation** - Immediate type validation with helpful error messages
- ✅ **Validation** - Laravel validation rules enforced on all changes
- ✅ **Caching** - Automatic caching with smart invalidation
- ✅ **Encryption** - Built-in encryption for sensitive data
- ✅ **Audit Trail** - Track all changes with user attribution
- ✅ **Global & Model-Scoped** - Settings for entire app or specific models

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
- `app/SchemaSettings/GlobalSettings.php` - Application-wide settings
- `app/SchemaSettings/UserSettings.php` - User-specific settings
- `app/SchemaSettings/SchemaSettingServiceProvider.php` - Service provider for registering schemas

### 3. Define Your Settings Schema

Edit `app/SchemaSettings/GlobalSettings.php`:

```php
<?php

namespace App\SchemaSettings;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class GlobalSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return null; // Null indicates global scope
    }

    public static function registerConfigurables(): array
    {
        return [
            // Basic Settings
            ConfigurableItem::make('site_name')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('Awesome App')
                ->group('general')
                ->label('Site Name')
                ->description('The name of your application')
                ->rules(['required', 'min:3', 'max:255']),
            
            ConfigurableItem::make('site_description')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('Welcome to our application')
                ->group('general')
                ->label('Site Description')
                ->description('A brief description of your site'),
        ];
    }
}
```

### 4. Register Your Schema

#### Option 1: Using the Generated Service Provider (Recommended)

The package generates a `SchemaSettingServiceProvider` for you. Register it in `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\SchemaSettings\SchemaSettingServiceProvider::class, // Add this line
];
```

The generated service provider will automatically register your schema classes:

```php
<?php

namespace App\SchemaSettings;

use Illuminate\Support\ServiceProvider;
use SgFlores\SchemaSetting\Facades\Settings;

class SchemaSettingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your schema classes here
        Settings::register(GlobalSettings::class);
        Settings::register(UserSettings::class);
    }
}
```

#### Option 2: Manual Registration

Alternatively, register schemas directly in your `AppServiceProvider`:

```php
use SgFlores\SchemaSetting\Facades\Settings;
use App\SchemaSettings\GlobalSettings;

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

## Model-Scoped Settings

**Define a model-scoped schema:**

```php
// app/SchemaSettings/UserSettings.php
namespace App\SchemaSettings;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class UserSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return \App\Models\User::class;
    }

    public static function registerConfigurables(): array
    {
        return [
            // Notification Settings
            ConfigurableItem::make('email_notifications')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Email Notifications')
                ->description('Receive notifications via email'),

            ConfigurableItem::make('timezone')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('UTC')
                ->group('localization')
                ->label('Timezone')
                ->description('Your timezone for date/time display')
                ->rules(['required', 'timezone']),
        ];
    }
}
```

**Add the trait to your model:**

```php
use SgFlores\SchemaSetting\Traits\HasSettings;

class User extends Model
{
    use HasSettings;
}
```

**Use settings on model instances:**

```php
// Get user-specific setting
$notifications = $user->setting('email_notifications');

// Set user-specific setting
$user->setSetting('timezone', 'America/New_York');
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

## Enhanced Type Validation

The package includes enhanced validation that catches type mismatches during the fluent chain definition. This provides immediate feedback and prevents runtime errors.

```php
// ❌ This will throw InvalidSchemaException immediately
ConfigurableItem::make('setting')
    ->type(ConfigurableItem::TYPE_INTEGER)
    ->default('hello'); // Type mismatch!

// ✅ This works correctly
ConfigurableItem::make('setting')
    ->type(ConfigurableItem::TYPE_STRING)
    ->default('hello');
```

## Artisan Commands

```bash
# List all registered settings
php artisan schema-settings:list

# Get a setting value
php artisan schema-settings:get site_name

# Set a setting value
php artisan schema-settings:set site_name "My App"

# Clear setting cache
php artisan schema-settings:clear-cache
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

    'validation' => [
        'strict_mode' => true,        // Validate during fluent chain
        'boot_validation' => true,    // Validate during registration  
        'enhanced_errors' => true,    // Provide detailed error messages
    ],
];
```

## Testing

The package includes a comprehensive test suite:

```bash
composer test
```

## Alternative Packages

If this package doesn't meet your needs, here are some alternative Laravel settings packages:

### [Spatie Laravel Settings](https://github.com/spatie/laravel-settings)
- **Approach**: Class-based settings with strongly typed properties
- **Strengths**: Well-established, comprehensive feature set, excellent documentation
- **Use Case**: When you prefer class-based settings with property definitions

### [Rawilk Laravel Settings](https://github.com/rawilk/laravel-settings)
- **Approach**: Database-driven settings with key-value storage
- **Strengths**: Simple implementation, database storage, caching support
- **Use Case**: When you need simple key-value settings without complex schemas

### Why Choose Schema Settings?

It offers a unique **schema-driven approach** that provides:

- ✅ **Fluent API** - Easy-to-read schema definitions with method chaining
- ✅ **Enhanced Validation** - Immediate type validation during schema definition
- ✅ **Model Scoping** - Settings can belong to specific models (users, teams, etc.)
- ✅ **Comprehensive Features** - Validation, caching, encryption, and audit trails out of the box
- ✅ **Developer Experience** - Helpful error messages and comprehensive documentation

## Documentation

For detailed information about advanced features and implementation details:

- 📖 **[Complete Documentation](DOCUMENTATION.md)** - Comprehensive guide with all features
- 🔍 **[Enhanced Validation](ENHANCED-VALIDATION.md)** - Detailed guide to enhanced type validation
- 🛠️ **[Helper Functions](HELPERS.md)** - Global helper reference
- 🔧 **[Package Development](PACKAGE-DEVELOPMENT-GUIDE.md)** - For contributors

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Author

**sgflores**
- Email: floresopic@gmail.com
- GitHub: [@sgflores](https://github.com/sgflores)

---

**Made with ❤️ for the Laravel community**