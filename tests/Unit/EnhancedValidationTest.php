<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Exceptions\InvalidSchemaException;
use SgFlores\SchemaSetting\Items\ConfigurableItem;
use SgFlores\SchemaSetting\Tests\Fixtures\TestEnum;
use SgFlores\SchemaSetting\Tests\TestCase;

/**
 * EnhancedValidationTest
 *
 * Tests the enhanced type/value validation in ConfigurableItem.
 * Ensures that type mismatches are caught during the fluent chain,
 * not just during registration.
 */
class EnhancedValidationTest extends TestCase
{
    #[Test]
    public function it_validates_type_mismatch_during_fluent_chain(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("Type mismatch for setting 'test_setting': Expected integer, but default value 'hello' is string");

        ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_INTEGER)
            ->default('hello'); // This should throw an exception
    }

    #[Test]
    public function it_validates_default_mismatch_when_type_is_set_after(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("Type mismatch for setting 'test_setting': Expected boolean, but default value 'yes' is string");

        ConfigurableItem::make('test_setting')
            ->default('yes')
            ->type(ConfigurableItem::TYPE_BOOLEAN); // This should throw an exception
    }

    #[Test]
    public function it_allows_correct_type_combinations(): void
    {
        // These should not throw exceptions
        $stringItem = ConfigurableItem::make('string_setting')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default('hello world');

        $intItem = ConfigurableItem::make('int_setting')
            ->type(ConfigurableItem::TYPE_INTEGER)
            ->default(42);

        $boolItem = ConfigurableItem::make('bool_setting')
            ->type(ConfigurableItem::TYPE_BOOLEAN)
            ->default(true);

        $floatItem = ConfigurableItem::make('float_setting')
            ->type(ConfigurableItem::TYPE_FLOAT)
            ->default(3.14);

        $arrayItem = ConfigurableItem::make('array_setting')
            ->type(ConfigurableItem::TYPE_ARRAY)
            ->default(['key' => 'value']);

        $datetimeItem = ConfigurableItem::make('datetime_setting')
            ->type(ConfigurableItem::TYPE_DATETIME)
            ->default('2023-01-01 12:00:00');

        $this->assertInstanceOf(ConfigurableItem::class, $stringItem);
        $this->assertInstanceOf(ConfigurableItem::class, $intItem);
        $this->assertInstanceOf(ConfigurableItem::class, $boolItem);
        $this->assertInstanceOf(ConfigurableItem::class, $floatItem);
        $this->assertInstanceOf(ConfigurableItem::class, $arrayItem);
        $this->assertInstanceOf(ConfigurableItem::class, $datetimeItem);
    }

    #[Test]
    public function it_allows_integer_defaults_for_float_type(): void
    {
        $item = ConfigurableItem::make('float_setting')
            ->type(ConfigurableItem::TYPE_FLOAT)
            ->default(42); // Integer should be allowed for float type

        $this->assertInstanceOf(ConfigurableItem::class, $item);
    }

    #[Test]
    public function it_provides_helpful_error_messages_for_integer_type(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('Use ->type(ConfigurableItem::TYPE_INTEGER) for numeric defaults or ->type(ConfigurableItem::TYPE_STRING) for text defaults.');

        ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_INTEGER)
            ->default('not a number');
    }

    #[Test]
    public function it_provides_helpful_error_messages_for_boolean_type(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('Use ->type(ConfigurableItem::TYPE_BOOLEAN) for true/false values or ->type(ConfigurableItem::TYPE_STRING) for text defaults.');

        ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_BOOLEAN)
            ->default('yes');
    }

    #[Test]
    public function it_provides_helpful_error_messages_for_float_type(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('Use ->type(ConfigurableItem::TYPE_FLOAT) for decimal numbers or ->type(ConfigurableItem::TYPE_STRING) for text defaults.');

        ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_FLOAT)
            ->default('not a number');
    }

    #[Test]
    public function it_provides_helpful_error_messages_for_array_type(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('Use ->type(ConfigurableItem::TYPE_ARRAY) for array defaults or ->type(ConfigurableItem::TYPE_STRING) for text defaults.');

        ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_ARRAY)
            ->default('not an array');
    }

    #[Test]
    public function it_provides_helpful_error_messages_for_datetime_type(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('Use ->type(ConfigurableItem::TYPE_DATETIME) for date/time defaults or ->type(ConfigurableItem::TYPE_STRING) for text defaults.');

        ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_DATETIME)
            ->default(['not', 'a', 'datetime']);
    }

    #[Test]
    public function it_does_not_validate_when_type_or_default_is_not_set(): void
    {
        // These should not throw exceptions
        $item1 = ConfigurableItem::make('test_setting')
            ->default('hello'); // No type set yet

        $item2 = ConfigurableItem::make('test_setting')
            ->type(ConfigurableItem::TYPE_STRING); // No default set yet

        $this->assertInstanceOf(ConfigurableItem::class, $item1);
        $this->assertInstanceOf(ConfigurableItem::class, $item2);
    }

    #[Test]
    public function it_validates_datetime_objects(): void
    {
        $item = ConfigurableItem::make('datetime_setting')
            ->type(ConfigurableItem::TYPE_DATETIME)
            ->default(new \DateTime('2023-01-01 12:00:00'));

        $this->assertInstanceOf(ConfigurableItem::class, $item);
    }

    #[Test]
    public function it_validates_enum_objects(): void
    {
        $item = ConfigurableItem::make('enum_setting')
            ->type(ConfigurableItem::TYPE_ENUM)
            ->enum(TestEnum::class)
            ->default(TestEnum::ACTIVE);

        $this->assertInstanceOf(ConfigurableItem::class, $item);
    }

    #[Test]
    public function it_handles_null_defaults_gracefully(): void
    {
        $item = ConfigurableItem::make('nullable_setting')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default(null);

        $this->assertInstanceOf(ConfigurableItem::class, $item);
    }

    #[Test]
    public function it_validates_complex_fluent_chains(): void
    {
        $this->expectException(InvalidSchemaException::class);

        ConfigurableItem::make('complex_setting')
            ->label('Complex Setting')
            ->description('A complex setting with validation')
            ->group('test')
            ->type(ConfigurableItem::TYPE_INTEGER)
            ->rules(['required', 'min:0'])
            ->default('not a number'); // This should throw
    }

    #[Test]
    public function it_preserves_other_properties_during_validation(): void
    {
        $item = ConfigurableItem::make('valid_setting')
            ->label('Valid Setting')
            ->description('A valid setting')
            ->group('test')
            ->type(ConfigurableItem::TYPE_STRING)
            ->rules(['required', 'max:255'])
            ->default('valid value');

        $this->assertEquals('valid_setting', $item->key);
        $this->assertEquals('Valid Setting', $item->label);
        $this->assertEquals('A valid setting', $item->description);
        $this->assertEquals('test', $item->group);
        $this->assertEquals(ConfigurableItem::TYPE_STRING, $item->type);
        $this->assertEquals(['required', 'max:255'], $item->rules);
        $this->assertEquals('valid value', $item->default);
    }
}
