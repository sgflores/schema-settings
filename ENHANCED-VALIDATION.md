# Enhanced Type Validation

This document describes the enhanced type validation feature added to the Schema Settings package.

## 🔍 Enhanced Type Validation

### Overview
Enhanced validation catches type mismatches during the fluent chain definition, not just during registration. This provides immediate feedback and prevents runtime errors.

### Configuration
```php
// config/schema-settings.php
return [
    'validation' => [
        'strict_mode' => true,        // Validate during fluent chain (type()/default())
        'boot_validation' => true,    // Validate during registration
        'enhanced_errors' => true,    // Provide detailed error messages with hints
    ],
];
```

Note: when you publish the package stubs, the provider will be placed under `app/Providers/SchemaSettings/SchemaSettingServiceProvider.php`.

### Usage

#### Immediate Validation
```php
// ❌ This will throw InvalidSchemaException immediately
ConfigurableItem::make('setting')
    ->type(ConfigurableItem::TYPE_INTEGER)
    ->default('hello'); // Type mismatch!

// ✅ This works correctly
ConfigurableItem::make('setting')
    ->type(ConfigurableItem::TYPE_STRING)
    ->default('hello');

// ✅ This also works (type set after default)
ConfigurableItem::make('setting')
    ->default(42)
    ->type(ConfigurableItem::TYPE_INTEGER);
```

#### Enhanced Error Messages
When validation fails, you get helpful error messages with suggestions:

```php
// Error: "Type mismatch for setting 'timeout': Expected integer, but default value 'not a number' is string. Use ->type(ConfigurableItem::TYPE_INTEGER) for numeric defaults or ->type(ConfigurableItem::TYPE_STRING) for text defaults."

ConfigurableItem::make('timeout')
    ->type(ConfigurableItem::TYPE_INTEGER)
    ->default('not a number');
```

#### Supported Type Combinations
```php
// String types
ConfigurableItem::make('name')->type(TYPE_STRING)->default('John');
ConfigurableItem::make('optional_name')->type(TYPE_STRING)->default(null); // ✅ Null allowed

// Integer types
ConfigurableItem::make('count')->type(TYPE_INTEGER)->default(42);
ConfigurableItem::make('optional_count')->type(TYPE_INTEGER)->default(null); // ✅ Null allowed

// Boolean types
ConfigurableItem::make('enabled')->type(TYPE_BOOLEAN)->default(true);
ConfigurableItem::make('optional_flag')->type(TYPE_BOOLEAN)->default(null); // ✅ Null allowed

// Float types (accepts integers too)
ConfigurableItem::make('price')->type(TYPE_FLOAT)->default(19.99);
ConfigurableItem::make('price')->type(TYPE_FLOAT)->default(20); // ✅ Also valid
ConfigurableItem::make('optional_price')->type(TYPE_FLOAT)->default(null); // ✅ Null allowed

// Array/JSON types
ConfigurableItem::make('config')->type(TYPE_ARRAY)->default(['key' => 'value']);
ConfigurableItem::make('optional_config')->type(TYPE_ARRAY)->default(null); // ✅ Null allowed

// DateTime types (accepts strings or DateTime objects)
ConfigurableItem::make('created_at')->type(TYPE_DATETIME)->default('2023-01-01 12:00:00');
ConfigurableItem::make('created_at')->type(TYPE_DATETIME)->default(new DateTime());
ConfigurableItem::make('optional_date')->type(TYPE_DATETIME)->default(null); // ✅ Null allowed

// Enum types
ConfigurableItem::make('status')->enum(\App\Enums\StatusEnum::class)->default(\App\Enums\StatusEnum::Active);
ConfigurableItem::make('optional_status')->enum(\App\Enums\StatusEnum::class)->default(null); // ✅ Null allowed
```

**Note:** Null defaults are always allowed for all types, representing optional settings.

### Benefits
- ✅ **Fail Fast**: Catch errors during definition, not runtime
- ✅ **Better Developer Experience**: Immediate feedback in IDE
- ✅ **Helpful Error Messages**: Clear suggestions for fixing issues
- ✅ **Type Safety**: Ensures consistency between types and defaults
- ✅ **Backward Compatible**: Existing code continues to work

### Testing
The enhanced validation is thoroughly tested in `tests/Unit/EnhancedValidationTest.php` with comprehensive test cases covering:
- Type mismatch validation during fluent chain
- Enhanced error messages with helpful hints
- All supported type combinations
- Edge cases and error handling
