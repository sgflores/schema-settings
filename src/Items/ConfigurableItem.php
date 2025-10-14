<?php

namespace SgFlores\SchemaSetting\Items;

use SgFlores\SchemaSetting\Exceptions\InvalidSchemaException;

/**
 * ConfigurableItem
 * 
 * A fluent builder class for defining individual settings in a schema.
 * Each ConfigurableItem represents one setting with its type, validation rules,
 * default value, and metadata (label, description, group, etc.).
 * 
 * This class uses a fluent interface pattern for easy schema definition.
 * 
 * Supported Features:
 * - 8 data types (string, integer, boolean, float, array, json, datetime, enum)
 * - Laravel validation rules
 * - Encryption support
 * - Readonly enforcement
 * - Option constraints
 * - Grouping and labeling for UI organization
 * 
 * @package SgFlores\SchemaSetting\Items
 * 
 */
class ConfigurableItem
{
    // Type Constants
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_FLOAT = 'float';
    public const TYPE_ARRAY = 'array';
    public const TYPE_JSON = 'json';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_ENUM = 'enum';

    public const ALLOWED_TYPES = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_FLOAT,
        self::TYPE_ARRAY,
        self::TYPE_JSON,
        self::TYPE_DATETIME,
        self::TYPE_ENUM,
    ];

    /** @var string The unique key identifier for this setting */
    public string $key;
    
    /** @var string The data type (one of TYPE_* constants) */
    public string $type = self::TYPE_STRING;
    
    /** @var mixed The default value when no value is set */
    public mixed $default = null;
    
    /** @var array Laravel validation rules for this setting */
    public array $rules = [];
    
    /** @var string|null Group name for organizing settings in UI */
    public ?string $group = null;
    
    /** @var string|null Human-readable label for this setting */
    public ?string $label = null;
    
    /** @var string|null Detailed description of what this setting does */
    public ?string $description = null;
    
    /** @var bool Whether this setting should be encrypted in storage */
    public bool $encrypted = false;
    
    /** @var bool Whether this setting can only be modified programmatically */
    public bool $readonly = false;
    
    /** @var string|null The enum class name for TYPE_ENUM settings */
    public ?string $enumClass = null;
    
    /** @var array Allowed values for this setting (auto-generates validation) */
    public array $options = [];

    /**
     * Create a new ConfigurableItem instance with the given key.
     * 
     * This is the starting point for building a setting definition using the fluent interface.
     * 
     * @param string $key The unique identifier for this setting (e.g., 'site_name', 'theme')
     * @return static
     */
    public static function make(string $key): static
    {
        $instance = new static;
        $instance->key = $key;
        return $instance;
    }

    /**
     * Set the data type for this setting.
     * 
     * The type determines how the value is stored, validated, and cast when retrieved.
     * Must be one of the TYPE_* constants.
     * 
     * @param string $type One of the TYPE_* constants (e.g., TYPE_STRING, TYPE_INTEGER)
     * @return static
     * @throws InvalidSchemaException If the type is not in ALLOWED_TYPES
     */
    public function type(string $type): static
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidSchemaException(
                "Invalid type '{$type}'. Allowed types: " . implode(', ', self::ALLOWED_TYPES),
                $this->key
            );
        }
        
        $this->type = $type;
        return $this;
    }

    /**
     * Set the default value for this setting.
     * 
     * The default is used when no value has been set in the database.
     * The default value type should match the declared type.
     * 
     * @param mixed $default The default value
     * @return static
     */
    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Set Laravel validation rules for this setting.
     * 
     * Rules are validated when a value is set. Can be provided as array or pipe-separated string.
     * 
     * @param array<int, string>|string $rules Laravel validation rules
     * @return static
     */
    public function rules(array|string $rules): static
    {
        $this->rules = is_array($rules) ? $rules : [$rules];
        return $this;
    }

    /**
     * Set the group name for organizing settings.
     * 
     * Groups help organize settings in administrative interfaces.
     * Multiple settings can share the same group.
     * 
     * @param string $group The group name (e.g., 'appearance', 'notifications', 'security')
     * @return static
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Set a human-readable label for this setting.
     * 
     * Labels are displayed in UIs instead of the technical key name.
     * 
     * @param string $label The display label
     * @return static
     */
    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set a detailed description for this setting.
     * 
     * Descriptions provide additional context about what the setting does
     * and how it affects the application.
     * 
     * @param string $description The description text
     * @return static
     */
    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Mark this setting as encrypted.
     * 
     * Encrypted settings are automatically encrypted when stored and decrypted
     * when retrieved. Useful for API keys, passwords, and sensitive data.
     * 
     * @param bool $encrypted Whether to encrypt this setting (default: true)
     * @return static
     */
    public function encrypted(bool $encrypted = true): static
    {
        $this->encrypted = $encrypted;
        return $this;
    }

    /**
     * Mark this setting as readonly.
     * 
     * Readonly settings cannot be modified or deleted at runtime.
     * They can only be set programmatically during installation or migrations.
     * Useful for system settings that shouldn't change.
     * 
     * @param bool $readonly Whether this setting is readonly (default: true)
     * @return static
     * @throws ReadonlySettingException When attempting to modify a readonly setting
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Set the enum class for this setting (PHP 8.1+ backed enums).
     * 
     * Automatically sets the type to TYPE_ENUM and validates the enum class exists.
     * Values will be automatically cast to/from the enum when getting/setting.
     * 
     * @param string $enumClass The fully qualified enum class name
     * @return static
     * @throws InvalidSchemaException If the class is not a valid enum
     */
    public function enum(string $enumClass): static
    {
        if (!enum_exists($enumClass)) {
            throw new InvalidSchemaException(
                "Class '{$enumClass}' is not a valid enum.",
                $this->key
            );
        }
        
        $this->type = self::TYPE_ENUM;
        $this->enumClass = $enumClass;
        return $this;
    }

    /**
     * Set allowed values (options) for this setting.
     * 
     * Automatically generates an 'in' validation rule to restrict values.
     * All options must be scalar values (string, int, float, bool).
     * 
     * @param array<int, scalar> $options Array of allowed values
     * @return static
     * @throws InvalidSchemaException If options array is empty or contains non-scalar values
     */
    public function options(array $options): static
    {
        // Validate options array is not empty
        if (empty($options)) {
            throw new InvalidSchemaException(
                "Options array cannot be empty for setting '{$this->key}'.",
                $this->key
            );
        }

        // Validate all options are scalar values
        foreach ($options as $option) {
            if (!is_scalar($option)) {
                throw new InvalidSchemaException(
                    "All options must be scalar values for setting '{$this->key}'.",
                    $this->key
                );
            }
        }

        $this->options = $options;
        
        // Auto-generate validation rule for options
        $this->rules = array_merge($this->rules, ['in:' . implode(',', $options)]);
        
        return $this;
    }

    /**
     * Validate this schema item definition.
     * 
     * Checks that:
     * - A type is set
     * - Enum types have an enumClass
     * - Default value type matches the declared type
     * 
     * Called automatically during schema registration to catch definition errors early.
     * 
     * @return void
     * @throws InvalidSchemaException If any validation fails
     */
    public function validate(): void
    {
        // Validate type is set
        if (empty($this->type)) {
            throw new InvalidSchemaException(
                "Type must be set for setting '{$this->key}'.",
                $this->key
            );
        }

        // Validate enum has enumClass
        if ($this->type === self::TYPE_ENUM && empty($this->enumClass)) {
            throw new InvalidSchemaException(
                "Enum type requires enumClass for setting '{$this->key}'.",
                $this->key
            );
        }

        // Validate default value type matches declared type
        if ($this->default !== null) {
            $this->validateDefaultType();
        }
    }

    /**
     * Validate that the default value matches the declared type.
     * 
     * Ensures type safety by checking that defaults are compatible with
     * the setting's declared type (e.g., string default for string type).
     * 
     * @return void
     * @throws InvalidSchemaException If the default value type doesn't match
     */
    protected function validateDefaultType(): void
    {
        $valid = match ($this->type) {
            self::TYPE_STRING => is_string($this->default),
            self::TYPE_INTEGER => is_int($this->default),
            self::TYPE_BOOLEAN => is_bool($this->default),
            self::TYPE_FLOAT => is_float($this->default) || is_int($this->default),
            self::TYPE_ARRAY, self::TYPE_JSON => is_array($this->default),
            self::TYPE_DATETIME => is_string($this->default) || $this->default instanceof \DateTimeInterface,
            self::TYPE_ENUM => $this->enumClass && $this->default instanceof $this->enumClass,
            default => true,
        };

        if (!$valid) {
            throw new InvalidSchemaException(
                "Default value type mismatch for setting '{$this->key}'. Expected {$this->type}, got " . gettype($this->default),
                $this->key
            );
        }
    }

    /**
     * Convert this ConfigurableItem to an array representation.
     * 
     * Used internally by the SettingsManager to store and retrieve schema configuration.
     * Includes all properties: key, type, default, rules, group, label, description,
     * encrypted, readonly, enumClass, and options.
     * 
     * @return array<string, mixed> Associative array of all properties
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'default' => $this->default,
            'rules' => $this->rules,
            'group' => $this->group,
            'label' => $this->label,
            'description' => $this->description,
            'encrypted' => $this->encrypted,
            'readonly' => $this->readonly,
            'enumClass' => $this->enumClass,
            'options' => $this->options,
        ];
    }
}
