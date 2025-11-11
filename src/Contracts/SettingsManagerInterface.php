<?php

namespace SgFlores\SchemaSetting\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use SgFlores\SchemaSetting\Exceptions\InvalidConfigurableException;
use SgFlores\SchemaSetting\Exceptions\ReadonlySettingException;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;

/**
 * SettingsManagerInterface
 *
 * Interface for the settings manager that handles schema registration,
 * CRUD operations, caching, and validation of application settings.
 *
 * This interface allows for dependency injection and makes the package
 * more testable and extensible.
 */
interface SettingsManagerInterface
{
    /**
     * Register a configurable class and compile its schema.
     *
     * @throws InvalidConfigurableException
     */
    public function register(string $class): void;

    /**
     * Retrieve a setting value.
     *
     * @throws SettingNotFoundException
     */
    public function get(string $key, ?Model $model = null): mixed;

    /**
     * Retrieve a setting value or throw exception.
     *
     * @throws SettingNotFoundException
     */
    public function getOrFail(string $key, ?Model $model = null): mixed;

    /**
     * Set/Update a setting value.
     *
     * @throws SettingNotFoundException
     * @throws ReadonlySettingException
     * @throws ValidationException
     */
    public function set(string $key, mixed $value, ?Model $model = null): bool;

    /**
     * Delete a setting.
     *
     * @throws SettingNotFoundException
     * @throws ReadonlySettingException
     */
    public function delete(string $key, ?Model $model = null): bool;

    /**
     * Get multiple settings at once.
     *
     * @throws SettingNotFoundException
     */
    public function getMultiple(array $keys, ?Model $model = null): array;

    /**
     * Set multiple settings at once.
     *
     * @throws SettingNotFoundException
     * @throws ReadonlySettingException
     * @throws ValidationException
     */
    public function setMultiple(array $settings, ?Model $model = null): bool;

    /**
     * Get all settings for a scope.
     */
    public function all(?Model $model = null): array;

    /**
     * Check if a setting exists in schema.
     */
    public function has(string $key, ?Model $model = null): bool;

    /**
     * Get the schema for a scope.
     *
     * Returns array of ConfigurableItem instances keyed by setting name.
     * If no scope specified, returns all registered schemas.
     *
     * @return array<string, ConfigurableItem>|array<string, array<string, ConfigurableItem>>
     */
    public function getSchema(?string $scopeKey = null): array;

    /**
     * Get schema configuration with persisted values for form generation.
     *
     * Returns the schema configuration with an added 'value' property containing
     * the persisted database value (or default if not set). Perfect for generating
     * frontend forms that need both the field definition and current values.
     *
     * @param  array<int, string>|string  $keys  Array of setting keys or single key (empty = all keys)
     * @param  Model|null  $model  Optional Eloquent model instance for model-scoped settings
     * @return array<string, array<string, mixed>> Associative array of schema configurations with values
     *
     * @throws SettingNotFoundException If any key is not found in schema
     */
    public function getSchemaWithValues(array|string $keys, ?Model $model = null): array;

    /**
     * Clear cached settings.
     */
    public function clearCache(?string $scopeKey = null, ?int $referenceId = null): void;
}
