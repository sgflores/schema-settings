<?php

namespace SgFlores\SchemaSetting\Contracts;

use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Exceptions\ReadonlySettingException;
use SgFlores\SchemaSetting\Exceptions\InvalidConfigurableException;
use Illuminate\Validation\ValidationException;

/**
 * SettingsManagerInterface
 * 
 * Interface for the settings manager that handles schema registration,
 * CRUD operations, caching, and validation of application settings.
 * 
 * This interface allows for dependency injection and makes the package
 * more testable and extensible.
 * 
 * @package SgFlores\SchemaSetting\Contracts
 */
interface SettingsManagerInterface
{
    /**
     * Register a configurable class and compile its schema.
     * 
     * @param string $class
     * @return void
     * @throws InvalidConfigurableException
     */
    public function register(string $class): void;

    /**
     * Retrieve a setting value.
     * 
     * @param string $key
     * @param object|null $model
     * @return mixed
     * @throws SettingNotFoundException
     */
    public function get(string $key, ?object $model = null): mixed;

    /**
     * Retrieve a setting value or throw exception.
     * 
     * @param string $key
     * @param object|null $model
     * @return mixed
     * @throws SettingNotFoundException
     */
    public function getOrFail(string $key, ?object $model = null): mixed;

    /**
     * Set/Update a setting value.
     * 
     * @param string $key
     * @param mixed $value
     * @param object|null $model
     * @return bool
     * @throws SettingNotFoundException
     * @throws ReadonlySettingException
     * @throws ValidationException
     */
    public function set(string $key, mixed $value, ?object $model = null): bool;

    /**
     * Delete a setting.
     * 
     * @param string $key
     * @param object|null $model
     * @return bool
     * @throws SettingNotFoundException
     * @throws ReadonlySettingException
     */
    public function delete(string $key, ?object $model = null): bool;

    /**
     * Get multiple settings at once.
     * 
     * @param array $keys
     * @param object|null $model
     * @return array
     * @throws SettingNotFoundException
     */
    public function getMultiple(array $keys, ?object $model = null): array;

    /**
     * Set multiple settings at once.
     * 
     * @param array $settings
     * @param object|null $model
     * @return bool
     * @throws SettingNotFoundException
     * @throws ReadonlySettingException
     * @throws ValidationException
     */
    public function setMultiple(array $settings, ?object $model = null): bool;

    /**
     * Get all settings for a scope.
     * 
     * @param object|null $model
     * @return array
     */
    public function all(?object $model = null): array;

    /**
     * Check if a setting exists in schema.
     * 
     * @param string $key
     * @param object|null $model
     * @return bool
     */
    public function has(string $key, ?object $model = null): bool;

    /**
     * Get the schema for a scope.
     * 
     * @param string|null $scopeKey
     * @return array
     */
    public function getSchema(?string $scopeKey = null): array;

    /**
     * Clear cached settings.
     * 
     * @param string|null $scopeKey
     * @param int|null $referenceId
     * @return void
     */
    public function clearCache(?string $scopeKey = null, ?int $referenceId = null): void;
}

