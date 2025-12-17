# Schema Settings - Release Notes

## 🎉 Version 1.0.0 - Initial Release

**Release Date:** December 17, 2025

### Overview

**Schema Settings** is a powerful Laravel package that provides a type-safe, schema-driven approach to managing application configuration. Instead of storing settings as simple key-value pairs, you define a schema that specifies types, defaults, validation rules, and metadata for each setting. This ensures data integrity, type safety, and provides automatic validation, caching, encryption, and audit trails.

### ✨ Key Features

- **📋 Schema-Driven** - Define settings with types, defaults, and validation rules
- **🔒 Type Safety** - 11 data types with automatic casting (string, long_text, integer, boolean, float, array, json, date, time, datetime, enum)
- **✅ Enhanced Validation** - Immediate type validation during schema definition with helpful error messages
- **🔄 Dynamic Options** - Lazy-load database-dependent options with callbacks
- **⚡ Automatic Caching** - Built-in caching with smart invalidation
- **🔐 Encryption** - Built-in encryption for sensitive data
- **📝 Audit Trail** - Track all changes with user attribution
- **🌐 Global & Model-Scoped** - Settings for entire app or specific models
- **🛠️ Artisan Commands** - Developer tools for settings management
- **🌍 RESTful API** - Clean API endpoints for frontend consumption (`/api/schema-settings`)
- **📦 Laravel Auto-Discovery** - Zero configuration required for basic setup

### 📋 Requirements

- **PHP:** ^8.1
- **Laravel:** ^10.0|^11.0|^12.0
- **Dependencies:**
  - `illuminate/support` ^10.0|^11.0|^12.0
  - `illuminate/database` ^10.0|^11.0|^12.0
  - `illuminate/validation` ^10.0|^11.0|^12.0
  - `illuminate/cache` ^10.0|^11.0|^12.0

### 🚀 Installation

```bash
composer require sgflores/schema-settings
```

### 📖 Quick Start

1. **Publish Configuration** (Optional)
   ```bash
   php artisan vendor:publish --tag=schema-settings-config
   ```

2. **Publish Migrations** (Optional)
   ```bash
   php artisan vendor:publish --tag=schema-settings-migrations
   php artisan migrate
   ```

3. **Publish Example Schema Classes**
   ```bash
   php artisan vendor:publish --tag=schema-settings-configurables
   ```

4. **Define Your Settings Schema**
   ```php
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
               ConfigurableItem::make('site_name')
                   ->type(ConfigurableItem::TYPE_STRING)
                   ->default('Awesome App')
                   ->group('general')
                   ->label('Site Name')
                   ->description('The name of your application')
                   ->rules(['required', 'min:3', 'max:255']),
               
               ConfigurableItem::make('maintenance_mode')
                   ->type(ConfigurableItem::TYPE_BOOLEAN)
                   ->default(false)
                   ->group('general')
                   ->label('Maintenance Mode'),
               
               // Dynamic options example
               ConfigurableItem::make('default_role')
                   ->type(ConfigurableItem::TYPE_STRING)
                   ->default('user')
                   ->lazyOptions(function() {
                       return \App\Models\Role::pluck('name')->toArray();
                   }),
           ];
       }
   }
   ```

5. **Register Your Schema**
   ```php
   use SgFlores\SchemaSetting\Facades\Settings;
   use App\Providers\SchemaSettings\GlobalSettings;
   
   public function boot(): void
   {
       Settings::register(GlobalSettings::class);
   }
   ```

6. **Use in Your Application**
   ```php
   // Via Facade
   $siteName = Settings::get('site_name');
   Settings::set('site_name', 'My Awesome App');
   
   // Via Helper Functions
   $siteName = setting('site_name');
   set_setting('site_name', 'My Awesome App');
   
   // Via API
   GET /api/schema-settings?key=site_name
   GET /api/schema-settings?keys[]=site_name&keys[]=maintenance_mode
   ```

### 🎯 What's Included

#### Core Components

- **SettingsManager** - Manages schema registration, CRUD operations, validation, and caching
- **ConfigurableItem** - Fluent builder for defining individual settings
- **ConfigurableInterface** - Contract for defining setting schemas
- **Setting Model** - Eloquent model for storing settings
- **SettingHistory Model** - Eloquent model for audit trail
- **HasSettings Trait** - Trait for model-scoped settings
- **Settings Facade** - Easy access without dependency injection
- **SchemaSettingServiceProvider** - Laravel service provider with auto-discovery
- **SettingsController** - RESTful API controller for frontend consumption

#### Developer Tools

- **Artisan Commands:**
  - `php artisan schema-settings:list` - List all registered settings
  - `php artisan schema-settings:get {key}` - Get a setting value
  - `php artisan schema-settings:set {key} {value}` - Set a setting value
  - `php artisan schema-settings:clear-cache` - Clear setting cache

#### Features

- **11 Data Types** - string, long_text, integer, boolean, float, array, json, date, time, datetime, enum
- **Enhanced Type Validation** - Immediate validation during schema definition
- **Laravel Validation Rules** - Full Laravel validation support
- **Automatic Caching** - Configurable cache TTL with smart invalidation
- **Encryption Support** - Built-in encryption for sensitive settings
- **Audit Trail** - Complete history of all setting changes with user attribution
- **Model Scoping** - Settings can belong to specific models (users, teams, etc.)
- **Dynamic Options** - Lazy-load database-dependent options via callbacks
- **Grouping** - Organize settings into logical groups
- **Readonly Settings** - Prevent modification of critical settings
- **API Routes** - RESTful endpoints for frontend form generation

### 📚 Documentation

- **[README.md](README.md)** - Quick start guide and basic usage
- **[DOCUMENTATION.md](DOCUMENTATION.md)** - Complete high-level documentation
- **[ENHANCED-VALIDATION.md](ENHANCED-VALIDATION.md)** - Enhanced type validation guide
- **[HELPERS.md](HELPERS.md)** - Global helper functions reference
- **[PACKAGE-DEVELOPMENT-GUIDE.md](PACKAGE-DEVELOPMENT-GUIDE.md)** - For package developers

### 🔧 Configuration

Default configuration (`config/schema-settings.php`):

```php
return [
    'table_name' => 'schema_settings',
    
    'cache' => [
        'enabled' => true,
        'store' => null,
        'prefix' => 'schema_settings_',
        'ttl' => 3600, // 1 hour
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

    'routes' => [
        'prefix' => env('SCHEMA_SETTINGS_ROUTE_PREFIX', 'api/schema-settings'),
        'middleware' => env('SCHEMA_SETTINGS_MIDDLEWARE', null),
        'name_prefix' => 'schema_settings.',
        'enabled' => env('SCHEMA_SETTINGS_ROUTES_ENABLED', true),
    ],
];
```

### 🎨 Usage Examples

#### Basic Settings
```php
ConfigurableItem::make('site_name')
    ->type(ConfigurableItem::TYPE_STRING)
    ->default('Awesome App')
    ->rules(['required', 'min:3', 'max:255'])
    ->group('general')
    ->label('Site Name')
    ->description('The name of your application');
```

#### Boolean Settings
```php
ConfigurableItem::make('maintenance_mode')
    ->type(ConfigurableItem::TYPE_BOOLEAN)
    ->default(false)
    ->group('general')
    ->label('Maintenance Mode');
```

#### Encrypted Settings
```php
ConfigurableItem::make('api_key')
    ->type(ConfigurableItem::TYPE_STRING)
    ->default('')
    ->encrypted()
    ->group('security')
    ->label('API Key');
```

#### Readonly Settings
```php
ConfigurableItem::make('version')
    ->type(ConfigurableItem::TYPE_STRING)
    ->default('1.0.0')
    ->readonly()
    ->group('system')
    ->label('Application Version');
```

#### Dynamic Options
```php
ConfigurableItem::make('default_role')
    ->type(ConfigurableItem::TYPE_STRING)
    ->default('user')
    ->label('Default Role')
    ->lazyOptions(function() {
        return \App\Models\Role::pluck('name')->toArray();
    });
```

#### Model-Scoped Settings
```php
// Define schema for User model
class UserSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return \App\Models\User::class;
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('email_notifications')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Email Notifications'),
        ];
    }
}

// Use in User model
use SgFlores\SchemaSetting\Traits\HasSettings;

class User extends Model
{
    use HasSettings;
}

// Use settings on model instances
$user->setting('email_notifications');
$user->setSetting('email_notifications', false);
```

### 🌐 API Endpoints

**Get Settings Schema with Values**

```http
GET /api/schema-settings?key=site_name
GET /api/schema-settings?keys[]=site_name&keys[]=maintenance_mode
GET /api/schema-settings
```

**Response Example:**

```json
{
    "success": true,
    "data": {
        "site_name": {
            "key": "site_name",
            "type": "string",
            "default": "Awesome App",
            "rules": ["required", "min:3", "max:255"],
            "group": "general",
            "label": "Site Name",
            "description": "The name of your application",
            "encrypted": false,
            "readonly": false,
            "enumClass": null,
            "options": [],
            "value": "My Current Site Name"
        }
    }
}
```

### 🧪 Testing

Run the test suite:

```bash
composer test
```

### 🤝 Contributing

We welcome contributions! Please see our contributing guidelines:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

### 👨‍💻 Author

**sgflores**
- Email: floresopic@gmail.com
- GitHub: [@sgflores](https://github.com/sgflores)

### 🙏 Acknowledgments

Built with ❤️ for the Laravel community.

---

**Made with ❤️ for the Laravel community**

