# Schema Settings - Helper Functions

## Overview

The package includes global helper functions for easy access to settings throughout your application.

---

## Global Helper Functions

All helper functions are automatically loaded and available globally in your Laravel application.

### `setting($key, $default = null, $model = null)`

Get a setting value with optional default fallback.

```php
use Illuminate\Database\Eloquent\Model;

// Get global setting
$siteName = setting('site_name');

// With default value
$theme = setting('theme', 'light');

// For a specific model (must be an Eloquent Model instance)
$userNotifications = setting('email_notifications', true, $user);
```

**Parameters:**
- `$key` (string): The setting key
- `$default` (mixed): Default value if setting doesn't exist or error occurs
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** mixed - The setting value or default

---

### `set_setting($key, $value, $model = null)`

Set a setting value.

```php
// Set global setting
set_setting('site_name', 'My Awesome Site');

// Set for a specific model (must be an Eloquent Model instance)
set_setting('email_notifications', false, $user);
```

**Parameters:**
- `$key` (string): The setting key
- `$value` (mixed): The value to set
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** bool - True on success, false on failure

---

### `has_setting($key, $model = null)`

Check if a setting exists in the schema.

```php
// Check global setting
if (has_setting('site_name')) {
    // Setting exists
}

// Check model-scoped setting
if (has_setting('email_notifications', $user)) {
    // User has notification setting
}
```

**Parameters:**
- `$key` (string): The setting key
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** bool

---

### `delete_setting($key, $model = null)`

Delete a setting (returns to default value).

```php
// Delete global setting
delete_setting('site_name');

// Delete model-scoped setting
delete_setting('email_notifications', $user);
```

**Parameters:**
- `$key` (string): The setting key
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** bool - True on success, false on failure

---

### `settings($keys, $model = null)`

Get multiple settings at once.

```php
// Get multiple global settings
$config = settings(['site_name', 'maintenance_mode', 'max_users']);

// Get multiple user settings
$userPrefs = settings(['email_notifications', 'timezone'], $user);
```

**Parameters:**
- `$keys` (array): Array of setting keys
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** array - Associative array of key => value pairs

---

### `all_settings($model = null)`

Get all settings for a scope.

```php
// Get all global settings
$globalSettings = all_settings();

// Get all user settings
$userSettings = all_settings($user);
```

**Parameters:**
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** array - All settings for the scope

---

### `schema_with_values($keys = [], $model = null)`

Get schema configuration with persisted values for form generation.

```php
// Get schema with values for specific keys
$formData = schema_with_values(['site_name', 'site_description', 'maintenance_mode']);

// Single key as string
$single = schema_with_values('site_name');

// Get all settings schema with values
$allSchema = schema_with_values([]);

// Model-scoped settings
$userSchema = schema_with_values(['theme', 'timezone'], $user);
```

**Parameters:**
- `$keys` (array|string): Array of setting keys or single key (empty = all keys)
- `$model` (Model|null): Optional Eloquent model instance for scoped settings

**Returns:** array - Schema configuration with 'value' property for each setting

---

## Usage Examples

### In API Controllers

```php
class SettingsController extends Controller
{
    public function index()
    {
        // Get global settings
        $config = settings([
            'site_name',
            'maintenance_mode',
            'max_users'
        ]);
        
        return response()->json($config);
    }
    
    public function userSettings()
    {
        $user = auth()->user();
        
        // Get user-specific settings
        $userPrefs = settings([
            'email_notifications',
            'timezone'
        ], $user);
        
        return response()->json($userPrefs);
    }
    
    public function updateUserSetting(Request $request)
    {
        $user = auth()->user();
        
        set_setting($request->key, $request->value, $user);
        
        return response()->json([
            'success' => true,
            'value' => setting($request->key, null, $user)
        ]);
    }
}
```

### In Models

```php
class User extends Authenticatable
{
    use HasSettings;
    
    public function getNotificationPreference()
    {
        return setting('email_notifications', true, $this);
        // or
        return $this->setting('email_notifications');
    }
    
    public function updateNotificationPreference($enabled)
    {
        return set_setting('email_notifications', $enabled, $this);
        // or
        return $this->setSetting('email_notifications', $enabled);
    }
}
```

### In Service Classes

```php
class NotificationService
{
    public function shouldSendEmail(User $user): bool
    {
        // Get user preference
        $emailEnabled = setting('email_notifications', true, $user);
        
        // Also check global setting
        $globalNotificationsEnabled = setting('notifications_enabled', true);
        
        return $emailEnabled && $globalNotificationsEnabled;
    }
}
```

### Batch Operations

```php
// Get multiple settings efficiently
$config = settings([
    'site_name',
    'site_description',
    'contact_email',
    'social_links'
]);

// Use in views
view('contact', $config);
```

---

## Error Handling

All helper functions handle errors gracefully:

- If a setting doesn't exist, they return the default value
- If validation fails, `set_setting()` returns `false`
- If an exception occurs, helpers fail silently and return safe defaults

```php
// Safe to use even if setting doesn't exist
$value = setting('non_existent_key', 'safe_default'); // Returns 'safe_default'

// Safe to check
if (has_setting('non_existent_key')) { // Returns false, no error
    // ...
}
```

---

## Performance Tips

1. **Cache Results:** Settings are automatically cached by the manager
2. **Batch Reads:** Use `settings()` instead of multiple `setting()` calls
3. **Model Scoped:** Settings are isolated per model instance

```php
// ❌ Multiple calls
$notifications = setting('email_notifications', null, $user);
$timezone = setting('timezone', null, $user);

// ✅ Single batch call
$userSettings = settings(['email_notifications', 'timezone'], $user);
```

---

## Comparison with Facade

| Helper Function | Facade Equivalent |
|----------------|-------------------|
| `setting('key')` | `Settings::get('key')` |
| `set_setting('key', 'val')` | `Settings::set('key', 'val')` |
| `has_setting('key')` | `Settings::has('key')` |
| `delete_setting('key')` | `Settings::delete('key')` |
| `settings(['a', 'b'])` | `Settings::getMultiple(['a', 'b'])` |
| `all_settings()` | `Settings::all()` |
| `schema_with_values(['a', 'b'])` | `Settings::getSchemaWithValues(['a', 'b'])` |

**Use helpers for:** Simple access, views, quick operations  
**Use facade for:** Complex operations, dependency injection, service classes

## API Routes

The package also provides API routes for frontend integration. See the main [README.md](README.md#api-routes) or [DOCUMENTATION.md](DOCUMENTATION.md#api-routes) for detailed API documentation.

**Quick Reference:**
- **Endpoint**: `GET /api/schema-settings`
- **Parameters**: `?key=setting_name` or `?keys[]=name1&keys[]=name2`
- **Returns**: Schema configuration with current values for form generation

