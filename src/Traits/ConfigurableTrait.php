<?php

namespace SgFlores\SchemaSetting\Traits;

use SgFlores\SchemaSetting\Facades\Settings;

/**
 * ConfigurableTrait
 * 
 * Trait for Eloquent models to provide convenient instance methods for managing settings.
 * Add this trait to any model that has associated settings defined in a schema.
 * 
 * This trait provides a cleaner API for working with model-scoped settings:
 * Instead of: Settings::get('theme', $user)
 * You can write: $user->setting('theme')
 * 
 * All methods automatically pass the current model instance to the SettingsManager,
 * ensuring settings are scoped to this specific model.
 * 
 * @package SgFlores\SchemaSetting\Traits
 */
trait ConfigurableTrait
{
    /**
     * Get a setting value for this model instance.
     * 
     * Retrieves a setting value that is scoped to this specific model.
     * Automatically passes $this as the model parameter to the SettingsManager.
     * 
     * @param string $key The setting key to retrieve
     * @return mixed The setting value, cast to its defined type
     * @throws \SgFlores\SchemaSetting\Exceptions\SettingNotFoundException
     */
    public function setting(string $key): mixed
    {
        return Settings::get($key, $this);
    }
    
    /**
     * Set a setting value for this model instance.
     * 
     * Updates or creates a setting scoped to this specific model.
     * The value is validated against schema rules before saving.
     * 
     * @param string $key The setting key to set
     * @param mixed $value The value to store
     * @return bool True on success
     * @throws \SgFlores\SchemaSetting\Exceptions\SettingNotFoundException
     * @throws \SgFlores\SchemaSetting\Exceptions\ReadonlySettingException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setSetting(string $key, mixed $value): bool
    {
        return Settings::set($key, $value, $this);
    }

    /**
     * Delete a setting for this model instance.
     * 
     * Removes the setting from database, causing future gets to return the default value.
     * 
     * @param string $key The setting key to delete
     * @return bool True if deleted, false if didn't exist
     * @throws \SgFlores\SchemaSetting\Exceptions\SettingNotFoundException
     * @throws \SgFlores\SchemaSetting\Exceptions\ReadonlySettingException
     */
    public function deleteSetting(string $key): bool
    {
        return Settings::delete($key, $this);
    }

    /**
     * Get multiple settings for this model instance.
     * 
     * Optimized method that fetches all requested settings in a single database query.
     * 
     * @param array $keys Array of setting keys to retrieve
     * @return array Associative array of key => value pairs
     * @throws \SgFlores\SchemaSetting\Exceptions\SettingNotFoundException
     */
    public function settings(array $keys): array
    {
        return Settings::getMultiple($keys, $this);
    }

    /**
     * Get all settings defined for this model instance.
     * 
     * Returns every setting registered in the schema for this model's class.
     * Uses a single optimized database query.
     * 
     * @return array Associative array of all settings (key => value pairs)
     */
    public function allSettings(): array
    {
        return Settings::all($this);
    }

    /**
     * Set multiple settings for this model instance.
     * 
     * Updates multiple settings atomically in a database transaction.
     * If any validation fails, no settings are saved.
     * 
     * @param array $settings Associative array of key => value pairs
     * @return bool True on success
     * @throws \SgFlores\SchemaSetting\Exceptions\SettingNotFoundException
     * @throws \SgFlores\SchemaSetting\Exceptions\ReadonlySettingException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setSettings(array $settings): bool
    {
        return Settings::setMultiple($settings, $this);
    }
}