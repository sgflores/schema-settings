# Schema Settings - Comprehensive Test Suite

## Test Overview

- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test integrated functionality
- **Test Fixtures**: Reusable test data and models
- **Base Test Case**: Shared test setup and configuration

---

## Test Structure

```
tests/
├── Unit/
│   └── ConfigurableItemTest.php
├── Feature/
│   ├── SettingsManagerTest.php
│   ├── ModelScopedSettingsTest.php
│   ├── CachingTest.php
│   ├── ValidationTest.php
│   ├── AuditTrailTest.php
│   ├── EncryptionTest.php
│   └── RegistrationTest.php
├── Fixtures/
│   ├── TestUser.php
│   ├── TestTeam.php
│   ├── TestGlobalSettings.php
│   └── TestUserSettings.php
└── TestCase.php
```

---

## Unit Tests

### ConfigurableItemTest.php
Tests the ConfigurableItem builder class:

- ✅ Item creation and fluent interface
- ✅ All 8 type constants (string, integer, boolean, float, array, json, datetime, enum)
- ✅ Type validation
- ✅ Default values
- ✅ Validation rules (array and string format)
- ✅ Groups, labels, descriptions
- ✅ Encrypted and readonly flags
- ✅ Options with auto-validation
- ✅ Enum validation
- ✅ Schema validation
- ✅ Type mismatch detection
- ✅ toArray() conversion

---

## Feature Tests

### 1. SettingsManagerTest.php
Core functionality of the settings manager:

**Registration:**
- ✅ Register configurable classes
- ✅ Register model-scoped configurables
- ✅ Reject non-configurable classes
- ✅ Schema retrieval

**CRUD Operations:**
- ✅ Get default values
- ✅ Set and get string values
- ✅ Set and get boolean values
- ✅ Delete settings
- ✅ Check setting existence

**Type Casting:**
- ✅ Boolean casting (including "1" to true)
- ✅ Integer casting
- ✅ Float casting
- ✅ Array casting
- ✅ JSON casting

**Validation:**
- ✅ Validate against rules
- ✅ Validate min/max for integers
- ✅ Validate options
- ✅ Allow valid options

**Special Features:**
- ✅ Readonly enforcement
- ✅ Batch get/set operations
- ✅ Get all settings
- ✅ Facade integration
- ✅ Database storage
- ✅ Update existing settings
- ✅ Database deletion

### 2. ModelScopedSettingsTest.php
Model-specific settings functionality:

**Model Settings:**
- ✅ Get default values for models
- ✅ Set and get for specific models
- ✅ Settings isolation between models
- ✅ Different model types isolation

**Trait Methods:**
- ✅ `setting()` - get setting
- ✅ `setSetting()` - set setting
- ✅ `deleteSetting()` - delete setting
- ✅ `settings()` - get multiple
- ✅ `allSettings()` - get all
- ✅ `setSettings()` - set multiple

**Storage & Validation:**
- ✅ Correct reference storage
- ✅ Validation for model settings
- ✅ Facade with models
- ✅ Delete model settings
- ✅ JSON type handling
- ✅ Setting existence check
- ✅ Get all for model

### 3. CachingTest.php
Comprehensive caching behavior:

**Cache Operations:**
- ✅ Cache on first get
- ✅ Retrieve from cache
- ✅ Invalidate on set
- ✅ Invalidate on delete
- ✅ Separate model-scoped caches
- ✅ Manual cache clearing

**Cache Keys:**
- ✅ Readable cache keys
- ✅ Model-specific cache keys
- ✅ Global scope cache keys

**Cache Behavior:**
- ✅ Handle cache misses
- ✅ Cache default values
- ✅ Multiple operations caching
- ✅ Configuration respect
- ✅ Different types caching
- ✅ Specific invalidation only

### 4. ValidationTest.php
Extensive validation testing:

**Laravel Validation Rules:**
- ✅ Required rule
- ✅ Min length rule
- ✅ Max length rule
- ✅ Integer min rule
- ✅ Integer max rule
- ✅ Float min rule
- ✅ Float max rule

**Options & Custom Rules:**
- ✅ Options validation
- ✅ Valid options acceptance
- ✅ Timezone validation
- ✅ Integer in-list validation
- ✅ Valid values from in-list

**Special Cases:**
- ✅ Encrypted field validation
- ✅ Model-scoped validation
- ✅ Multiple rules on single field
- ✅ No validation when no rules
- ✅ Error messages provision
- ✅ Validate before database storage
- ✅ Validate on update

### 5. AuditTrailTest.php
Audit trail functionality:

**History Recording:**
- ✅ Record on create
- ✅ Record on update
- ✅ Record on delete
- ✅ Record old and new values
- ✅ Null old value on create
- ✅ Null new value on delete

**Model Scoped:**
- ✅ Model-scoped audit trail
- ✅ Separate history for different models

**Querying:**
- ✅ Multiple changes tracking
- ✅ Chronological ordering
- ✅ Query by key
- ✅ Query by action

**Special Cases:**
- ✅ All value types in history
- ✅ Timestamps recording
- ✅ Full lifecycle tracking
- ✅ Batch operations audit
- ✅ Encrypted settings audit

### 6. EncryptionTest.php
Encryption functionality:

**Encryption:**
- ✅ Encrypt marked fields
- ✅ Decrypt on retrieval
- ✅ Don't encrypt non-encrypted fields
- ✅ Handle encrypted updates

**Edge Cases:**
- ✅ Return default on decryption failure
- ✅ Separate encrypted/non-encrypted storage
- ✅ Cache decrypted values
- ✅ Validate before encryption
- ✅ Encrypt after validation

### 7. RegistrationTest.php
Schema registration testing:

**Registration:**
- ✅ Register global settings
- ✅ Register model-scoped settings
- ✅ Validate schema on registration
- ✅ Reject non-configurable classes
- ✅ Store all schema items
- ✅ Store item details correctly

**Multiple Registrations:**
- ✅ Register multiple classes
- ✅ Return all schemas
- ✅ Empty array for unregistered scope

**Property Preservation:**
- ✅ Preserve all properties
- ✅ Preserve validation rules
- ✅ Preserve special flags
- ✅ Check existence after registration
- ✅ Check model-scoped existence

---

## Test Fixtures

### TestUser.php
- Eloquent model with ConfigurableTrait
- Used for model-scoped testing

### TestTeam.php
- Eloquent model with ConfigurableTrait
- Used for testing different model types

### TestGlobalSettings.php
Comprehensive global settings schema featuring all data types and features:
- String, Boolean, Integer, Float types
- Array and JSON types
- DateTime type
- Encrypted settings
- Readonly settings
- Options/select fields
- Various validation rules

### TestUserSettings.php
Model-scoped settings schema featuring:
- String with options
- Boolean flags
- Integer with in-list validation
- Timezone validation
- JSON preferences
- Organized groups and labels

---

## Running Tests

```bash
# Run all tests
composer test

# Or with PHPUnit directly
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/ConfigurableItemTest.php

# Run specific test method
vendor/bin/phpunit --filter it_can_create_a_configurable_item

# Run only unit tests
vendor/bin/phpunit tests/Unit

# Run only feature tests
vendor/bin/phpunit tests/Feature

# Run with coverage (if Xdebug installed)
vendor/bin/phpunit --coverage-html build/coverage
```

---

## Test Configuration

- **Database**: SQLite in-memory
- **Framework**: Orchestra Testbench
- **PHP Version**: 8.1+
- **RefreshDatabase**: Yes (each test starts fresh)
- **Test Isolation**: Complete (no test affects another)

---

## Coverage Areas

✅ **100% Core Functionality Covered:**
- Schema registration
- Setting CRUD operations
- Type casting (all 8 types)
- Validation (all rule types)
- Caching (get, set, invalidate, clear)
- Audit trail (create, update, delete)
- Encryption (encrypt, decrypt, failures)
- Model scoping (isolation, trait methods)
- Batch operations (optimized with single query & transactions)
- Readonly enforcement
- Facade integration
- Database persistence

✅ **Edge Cases Covered:**
- Invalid types
- Missing keys
- Readonly violations
- Validation failures
- Cache misses
- Decryption failures
- Multiple model instances
- Different model types
- Corrupted data
- Null values

---

## Test Quality

- **Modern PHPUnit**: All tests use `#[Test]` attribute (PHPUnit 10+)
- **Descriptive names**: All tests use `it_should_do_something` naming
- **Isolated**: Each test is independent
- **Comprehensive**: Extensive coverage of all scenarios and edge cases
- **Fast**: Uses in-memory SQLite
- **Reliable**: No flaky tests
- **Maintainable**: Clear structure and fixtures

---

## Future Test Additions

When adding new features, ensure:
1. Unit tests for new classes
2. Feature tests for new functionality
3. Integration tests if needed
4. Edge case coverage
5. Update this document

