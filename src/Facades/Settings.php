<?php

namespace SgFlores\SchemaSetting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Settings Facade
 * 
 * Provides static access to the SettingsManager instance.
 * This is the primary way to interact with the settings system.
 * 
 * Available Methods:
 * @method static void register(string $class) Register a configurable schema class
 * @method static mixed get(string $key, object|null $model = null) Get a setting value
 * @method static mixed getOrFail(string $key, object|null $model = null) Get a setting or throw exception
 * @method static bool set(string $key, mixed $value, object|null $model = null) Set a setting value
 * @method static bool delete(string $key, object|null $model = null) Delete a setting
 * @method static array getMultiple(array $keys, object|null $model = null) Get multiple settings
 * @method static bool setMultiple(array $settings, object|null $model = null) Set multiple settings
 * @method static array all(object|null $model = null) Get all settings for a scope
 * @method static bool has(string $key, object|null $model = null) Check if setting exists in schema
 * @method static array getSchema(string|null $scopeKey = null) Get schema configuration
 * @method static void clearCache(string|null $scopeKey = null, int|null $referenceId = null) Clear cached settings
 * 
 * @see \SgFlores\SchemaSetting\Manager\SettingsManager
 * @package SgFlores\SchemaSetting\Facades
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