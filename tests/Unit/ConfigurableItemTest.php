<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Items\ConfigurableItem;
use SgFlores\SchemaSetting\Exceptions\InvalidSchemaException;

class ConfigurableItemTest extends TestCase
{
    #[Test]
    public function it_can_create_a_configurable_item(): void
    {
        $item = ConfigurableItem::make('test_key');

        $this->assertEquals('test_key', $item->key);
        $this->assertEquals(ConfigurableItem::TYPE_STRING, $item->type);
        $this->assertNull($item->default);
    }

    #[Test]
    public function it_sets_default_type_to_string(): void
    {
        $item = ConfigurableItem::make('test');

        $this->assertEquals(ConfigurableItem::TYPE_STRING, $item->type);
    }

    #[Test]
    public function it_can_set_each_type(): void
    {
        $types = [
            ConfigurableItem::TYPE_STRING,
            ConfigurableItem::TYPE_INTEGER,
            ConfigurableItem::TYPE_BOOLEAN,
            ConfigurableItem::TYPE_FLOAT,
            ConfigurableItem::TYPE_ARRAY,
            ConfigurableItem::TYPE_JSON,
            ConfigurableItem::TYPE_DATETIME,
            ConfigurableItem::TYPE_ENUM,
        ];

        foreach ($types as $type) {
            $item = ConfigurableItem::make('test')->type($type);
            $this->assertEquals($type, $item->type);
        }
    }

    #[Test]
    public function it_throws_exception_for_invalid_type(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("Invalid type 'invalid_type'");

        ConfigurableItem::make('test')->type('invalid_type');
    }

    #[Test]
    public function it_can_set_default_value(): void
    {
        $item = ConfigurableItem::make('test')
            ->default('test value');

        $this->assertEquals('test value', $item->default);
    }

    #[Test]
    public function it_validates_type_mismatch_during_fluent_chain(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("Type mismatch for setting 'test': Expected integer, but default value 'hello' is string");

        ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_INTEGER)
            ->default('hello'); // This should throw an exception
    }

    #[Test]
    public function it_can_set_rules_as_array(): void
    {
        $item = ConfigurableItem::make('test')
            ->rules(['required', 'min:3']);

        $this->assertEquals(['required', 'min:3'], $item->rules);
    }

    #[Test]
    public function it_can_set_rules_as_string(): void
    {
        $item = ConfigurableItem::make('test')
            ->rules('required');

        $this->assertEquals(['required'], $item->rules);
    }

    #[Test]
    public function it_can_set_group(): void
    {
        $item = ConfigurableItem::make('test')
            ->group('appearance');

        $this->assertEquals('appearance', $item->group);
    }

    #[Test]
    public function it_can_set_label(): void
    {
        $item = ConfigurableItem::make('test')
            ->label('Test Setting');

        $this->assertEquals('Test Setting', $item->label);
    }

    #[Test]
    public function it_can_set_description(): void
    {
        $item = ConfigurableItem::make('test')
            ->description('A test setting');

        $this->assertEquals('A test setting', $item->description);
    }

    #[Test]
    public function it_can_set_encrypted_flag(): void
    {
        $item = ConfigurableItem::make('test')->encrypted();
        $this->assertTrue($item->encrypted);

        $item2 = ConfigurableItem::make('test')->encrypted(false);
        $this->assertFalse($item2->encrypted);
    }

    #[Test]
    public function it_can_set_readonly_flag(): void
    {
        $item = ConfigurableItem::make('test')->readonly();
        $this->assertTrue($item->readonly);

        $item2 = ConfigurableItem::make('test')->readonly(false);
        $this->assertFalse($item2->readonly);
    }

    #[Test]
    public function it_can_set_options(): void
    {
        $item = ConfigurableItem::make('test')
            ->options(['option1', 'option2', 'option3']);

        $this->assertEquals(['option1', 'option2', 'option3'], $item->options);
    }

    #[Test]
    public function it_auto_generates_validation_rule_for_options(): void
    {
        $item = ConfigurableItem::make('test')
            ->options(['light', 'dark']);

        $this->assertContains('in:light,dark', $item->rules);
    }

    #[Test]
    public function it_validates_enum_class_exists(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("is not a valid enum");

        ConfigurableItem::make('test')->enum('NonExistentEnum');
    }

    #[Test]
    public function it_validates_schema_item_successfully(): void
    {
        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default('value');

        $item->validate();

        $this->assertTrue(true); // No exception thrown
    }

    #[Test]
    public function it_throws_exception_when_default_type_mismatches_string(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("Type mismatch for setting 'test': Expected string, but default value '123' is integer");

        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default(123); // Integer instead of string

        $item->validate();
    }

    #[Test]
    public function it_throws_exception_when_default_type_mismatches_boolean(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_BOOLEAN)
            ->default('not a boolean');

        $item->validate();
    }

    #[Test]
    public function it_throws_exception_when_default_type_mismatches_integer(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_INTEGER)
            ->default('not an integer');

        $item->validate();
    }

    #[Test]
    public function it_accepts_integer_for_float_type(): void
    {
        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_FLOAT)
            ->default(10); // Integer is acceptable for float

        $item->validate();

        $this->assertTrue(true); // No exception
    }

    #[Test]
    public function it_throws_exception_when_default_type_mismatches_array(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_ARRAY)
            ->default('not an array');

        $item->validate();
    }

    #[Test]
    public function it_converts_to_array_correctly(): void
    {
        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default('value')
            ->group('test_group')
            ->label('Test')
            ->description('Test description')
            ->encrypted()
            ->readonly()
            ->rules(['required'])
            ->options(['a', 'b']);

        $array = $item->toArray();

        $this->assertEquals('test', $array['key']);
        $this->assertEquals(ConfigurableItem::TYPE_STRING, $array['type']);
        $this->assertEquals('value', $array['default']);
        $this->assertEquals('test_group', $array['group']);
        $this->assertEquals('Test', $array['label']);
        $this->assertEquals('Test description', $array['description']);
        $this->assertTrue($array['encrypted']);
        $this->assertTrue($array['readonly']);
        $this->assertContains('required', $array['rules']);
        $this->assertEquals(['a', 'b'], $array['options']);
    }

    #[Test]
    public function it_supports_fluent_interface(): void
    {
        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default('value')
            ->group('group')
            ->label('Label')
            ->description('Desc')
            ->encrypted()
            ->readonly()
            ->rules(['required']);

        $this->assertInstanceOf(ConfigurableItem::class, $item);
        $this->assertEquals('test', $item->key);
        $this->assertEquals(ConfigurableItem::TYPE_STRING, $item->type);
    }

    #[Test]
    public function it_allows_null_default_value(): void
    {
        $item = ConfigurableItem::make('test')
            ->type(ConfigurableItem::TYPE_STRING)
            ->default(null);

        $item->validate(); // Should not throw exception

        $this->assertNull($item->default);
    }
}
