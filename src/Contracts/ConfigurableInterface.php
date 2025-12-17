<?php

namespace SgFlores\SchemaSetting\Contracts;

use SgFlores\SchemaSetting\Items\ConfigurableItem;

/**
 * ConfigurableInterface
 *
 * Interface for defining schema-driven settings. Classes implementing this interface
 * can register settings for either global scope or model-specific scope.
 *
 * Usage:
 * - Return null from getKey() for global settings
 * - Return a model class name (e.g., User::class) for model-scoped settings
 * - Define all settings using ConfigurableItem builders in registerConfigurables()
 */
interface ConfigurableInterface
{
    /**
     * Get the unique scope key for this set of configurables.
     *
     * This method determines whether settings are global or tied to a specific model.
     *
     * @return string|null The model class name (e.g., App\Models\User::class) for model-scoped settings,
     *                     or null for global application settings.
     */
    public static function getKey(): ?string;

    /**
     * Define the schema for all configurable items in this scope.
     *
     * Each setting must be defined using ConfigurableItem::make() with appropriate
     * type, default value, validation rules, and other metadata.
     *
     * @return array<ConfigurableItem> An array of ConfigurableItem instances defining the schema.
     */
    public static function registerConfigurables(): array;
}
