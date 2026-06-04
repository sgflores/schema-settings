<?php

use Illuminate\Database\Eloquent\Model;
use SgFlores\SchemaSetting\Facades\Settings;

if (! function_exists('setting')) {
    /**
     * Get a setting value.
     */
    function setting(string $key, mixed $default = null, ?Model $model = null): mixed
    {
        try {
            return Settings::get($key, $model);
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (! function_exists('set_setting')) {
    /**
     * Set a setting value.
     */
    function set_setting(string $key, mixed $value, ?Model $model = null): bool
    {
        try {
            return Settings::set($key, $value, $model);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('has_setting')) {
    /**
     * Check if a setting exists in the schema.
     */
    function has_setting(string $key, ?Model $model = null): bool
    {
        try {
            return Settings::has($key, $model);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('delete_setting')) {
    /**
     * Delete a setting.
     */
    function delete_setting(string $key, ?Model $model = null): bool
    {
        try {
            return Settings::delete($key, $model);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('settings')) {
    /**
     * Get multiple settings at once.
     */
    function settings(array $keys, ?Model $model = null): array
    {
        try {
            return Settings::getMultiple($keys, $model);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (! function_exists('all_settings')) {
    /**
     * Get all settings for a scope.
     */
    function all_settings(?Model $model = null): array
    {
        try {
            return Settings::all($model);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (! function_exists('schema_with_values')) {
    /**
     * Get schema configuration with persisted values for form generation.
     *
     * @param  array|string  $keys  Array of setting keys or single key (empty = all keys)
     */
    function schema_with_values(array|string $keys = [], ?Model $model = null): array
    {
        try {
            return Settings::getSchemaWithValues($keys, $model);
        } catch (Exception $e) {
            return [];
        }
    }
}
