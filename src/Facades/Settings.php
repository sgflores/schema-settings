<?php

namespace SgFlores\SchemaSetting\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Settings Facade
 *
 * Provides static access to the SettingsManager instance.
 * This is the primary way to interact with the settings system.
 *
 * Available Methods:
 *
 * @method static void register(string $class) Register a configurable schema class
 * @method static mixed get(string $key, Model|null $model = null) Get a setting value
 * @method static mixed getOrFail(string $key, Model|null $model = null) Get a setting or throw exception
 * @method static bool set(string $key, mixed $value, Model|null $model = null) Set a setting value
 * @method static bool delete(string $key, Model|null $model = null) Delete a setting
 * @method static array<string, mixed> getMultiple(array $keys, Model|null $model = null) Get multiple settings
 * @method static bool setMultiple(array $settings, Model|null $model = null) Set multiple settings
 * @method static array<string, mixed> all(Model|null $model = null) Get all settings for a scope
 * @method static bool has(string $key, Model|null $model = null) Check if setting exists in schema
 * @method static array<string, ConfigurableItem>|array<string, array<string, ConfigurableItem>> getSchema(string|null $scopeKey = null) Get schema configuration (array of ConfigurableItem instances)
 * @method static array<string, array<string, mixed>> getSchemaWithValues(array|string $keys, Model|null $model = null) Get schema configuration with persisted values for form generation
 * @method static void clearCache(string|null $scopeKey = null, int|null $referenceId = null) Clear cached settings
 *
 * @see \SgFlores\SchemaSetting\Manager\SettingsManager
 */
class Settings extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The service container binding key
     */
    protected static function getFacadeAccessor(): string
    {
        return 'schema-settings';
    }
}
