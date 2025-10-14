# Schema Settings - Helper Functions

## Overview

The package includes global helper functions for easy access to settings throughout your application.

---

## Global Helper Functions

All helper functions are automatically loaded and available globally in your Laravel application.

### `setting($key, $default = null, $model = null)`

Get a setting value with optional default fallback.

```php
// Get global setting
$siteName = setting('site_name');

// With default value
$theme = setting('theme', 'light');

// For a specific model
$userTheme = setting('theme', 'light', $user);
```

**Parameters:**
- `$key` (string): The setting key
- `$default` (mixed): Default value if setting doesn't exist or error occurs
- `$model` (object|null): Optional model for scoped settings

**Returns:** mixed - The setting value or default

---

### `set_setting($key, $value, $model = null)`

Set a setting value.

```php
// Set global setting
set_setting('site_name', 'My Awesome Site');

// Set for a specific model
set_setting('theme', 'dark', $user);
```

**Parameters:**
- `$key` (string): The setting key
- `$value` (mixed): The value to set
- `$model` (object|null): Optional model for scoped settings

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
if (has_setting('theme', $user)) {
    // User has theme setting
}
```

**Parameters:**
- `$key` (string): The setting key
- `$model` (object|null): Optional model for scoped settings

**Returns:** bool

---

### `delete_setting($key, $model = null)`

Delete a setting (returns to default value).

```php
// Delete global setting
delete_setting('site_name');

// Delete model-scoped setting
delete_setting('theme', $user);
```

**Parameters:**
- `$key` (string): The setting key
- `$model` (object|null): Optional model for scoped settings

**Returns:** bool - True on success, false on failure

---

### `settings($keys, $model = null)`

Get multiple settings at once.

```php
// Get multiple global settings
$config = settings(['site_name', 'maintenance_mode', 'max_users']);

// Get multiple user settings
$userPrefs = settings(['theme', 'notifications_enabled'], $user);
```

**Parameters:**
- `$keys` (array): Array of setting keys
- `$model` (object|null): Optional model for scoped settings

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
- `$model` (object|null): Optional model for scoped settings

**Returns:** array - All settings for the scope

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
            'theme',
            'notifications_enabled',
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
    use ConfigurableTrait;
    
    public function getPreferredTheme()
    {
        return setting('theme', 'light', $this);
        // or
        return $this->setting('theme');
    }
    
    public function updateTheme($theme)
    {
        return set_setting('theme', $theme, $this);
        // or
        return $this->setSetting('theme', $theme);
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
$theme = setting('theme', null, $user);
$notifications = setting('notifications_enabled', null, $user);
$timezone = setting('timezone', null, $user);

// ✅ Single batch call
$userSettings = settings(['theme', 'notifications_enabled', 'timezone'], $user);
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

**Use helpers for:** Simple access, views, quick operations  
**Use facade for:** Complex operations, dependency injection, service classes

