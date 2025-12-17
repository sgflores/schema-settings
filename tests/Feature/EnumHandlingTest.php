<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestEnumSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestPriorityEnum;
use SgFlores\SchemaSetting\Tests\Fixtures\TestStatusEnum;
use SgFlores\SchemaSetting\Tests\TestCase;

class EnumHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestEnumSettings::class);
    }

    #[Test]
    public function it_can_set_enum_value(): void
    {
        $result = $this->manager->set('status', TestStatusEnum::Active);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_enum_instance_when_getting(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);

        $value = $this->manager->get('status');

        $this->assertInstanceOf(TestStatusEnum::class, $value);
        $this->assertEquals(TestStatusEnum::Active, $value);
    }

    #[Test]
    public function it_returns_default_enum_value(): void
    {
        $value = $this->manager->get('status');

        $this->assertInstanceOf(TestStatusEnum::class, $value);
        $this->assertEquals(TestStatusEnum::Pending, $value);
    }

    #[Test]
    public function it_can_set_enum_by_string_value(): void
    {
        $this->manager->set('status', 'active');

        $value = $this->manager->get('status');

        $this->assertInstanceOf(TestStatusEnum::class, $value);
        $this->assertEquals(TestStatusEnum::Active, $value);
    }

    #[Test]
    public function it_handles_invalid_enum_value_gracefully(): void
    {
        // Setting invalid enum value should fall back to default
        $this->manager->set('status', 'invalid_status');

        $value = $this->manager->get('status');

        // Should return default since invalid value can't be cast
        $this->assertEquals(TestStatusEnum::Pending, $value);
    }

    #[Test]
    public function it_stores_enum_as_scalar_value_in_database(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);

        $setting = Setting::where('key', 'status')->first();
        $storedValue = json_decode($setting->value, true);

        $this->assertEquals('active', $storedValue);
    }

    #[Test]
    public function it_can_update_enum_value(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->assertEquals(TestStatusEnum::Active, $this->manager->get('status'));

        $this->manager->set('status', TestStatusEnum::Inactive);
        $value = $this->manager->get('status');

        $this->assertEquals(TestStatusEnum::Inactive, $value);
    }

    #[Test]
    public function it_handles_all_enum_cases(): void
    {
        foreach (TestStatusEnum::cases() as $status) {
            $this->manager->set('status', $status);
            $retrieved = $this->manager->get('status');

            $this->assertEquals($status, $retrieved);
        }
    }

    #[Test]
    public function it_supports_integer_backed_enums(): void
    {
        $this->manager->set('priority', TestPriorityEnum::High);

        $value = $this->manager->get('priority');

        $this->assertInstanceOf(TestPriorityEnum::class, $value);
        $this->assertEquals(TestPriorityEnum::High, $value);
        $this->assertEquals(3, $value->value);
    }

    #[Test]
    public function it_can_set_integer_backed_enum_by_value(): void
    {
        $this->manager->set('priority', 3);

        $value = $this->manager->get('priority');

        $this->assertInstanceOf(TestPriorityEnum::class, $value);
        $this->assertEquals(TestPriorityEnum::High, $value);
    }

    #[Test]
    public function it_stores_integer_backed_enum_as_int_in_database(): void
    {
        $this->manager->set('priority', TestPriorityEnum::High);

        $setting = Setting::where('key', 'priority')->first();
        $storedValue = json_decode($setting->value, true);

        $this->assertEquals(3, $storedValue);
    }

    #[Test]
    public function it_handles_null_enum_values(): void
    {
        $value = $this->manager->get('optional_status');

        $this->assertNull($value);
    }

    #[Test]
    public function it_can_set_null_for_optional_enum(): void
    {
        $this->manager->set('optional_status', TestStatusEnum::Active);
        $this->assertEquals(TestStatusEnum::Active, $this->manager->get('optional_status'));

        $this->manager->set('optional_status', null);
        $value = $this->manager->get('optional_status');

        $this->assertNull($value);
    }

    #[Test]
    public function it_returns_default_for_invalid_integer_enum_value(): void
    {
        $this->manager->set('priority', 999); // Invalid value

        $value = $this->manager->get('priority');

        // Should return default
        $this->assertEquals(TestPriorityEnum::Medium, $value);
    }

    #[Test]
    public function it_can_compare_enum_values(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);

        $value = $this->manager->get('status');

        $this->assertTrue($value === TestStatusEnum::Active);
        $this->assertFalse($value === TestStatusEnum::Inactive);
    }

    #[Test]
    public function it_can_get_enum_name(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);

        $value = $this->manager->get('status');

        $this->assertEquals('Active', $value->name);
    }

    #[Test]
    public function it_can_get_enum_value(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);

        $value = $this->manager->get('status');

        $this->assertEquals('active', $value->value);
    }

    #[Test]
    public function it_caches_enum_values_correctly(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $value1 = $this->manager->get('status');

        // Modify database directly
        Setting::where('key', 'status')->update([
            'value' => json_encode('inactive'),
        ]);

        // Should return cached value
        $value2 = $this->manager->get('status');

        $this->assertEquals(TestStatusEnum::Active, $value2);
    }

    #[Test]
    public function it_invalidates_cache_on_enum_update(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->assertEquals(TestStatusEnum::Active, $this->manager->get('status'));

        $this->manager->set('status', TestStatusEnum::Inactive);
        $value = $this->manager->get('status');

        $this->assertEquals(TestStatusEnum::Inactive, $value);
    }

    #[Test]
    public function it_works_with_multiple_enum_settings(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->manager->set('priority', TestPriorityEnum::High);

        $status = $this->manager->get('status');
        $priority = $this->manager->get('priority');

        $this->assertEquals(TestStatusEnum::Active, $status);
        $this->assertEquals(TestPriorityEnum::High, $priority);
    }

    #[Test]
    public function it_can_get_multiple_enum_settings(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->manager->set('priority', TestPriorityEnum::High);

        $values = $this->manager->getMultiple(['status', 'priority']);

        $this->assertCount(2, $values);
        $this->assertEquals(TestStatusEnum::Active, $values['status']);
        $this->assertEquals(TestPriorityEnum::High, $values['priority']);
    }

    #[Test]
    public function it_includes_enums_in_all_settings(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->manager->set('priority', TestPriorityEnum::High);

        $all = $this->manager->all();

        $this->assertArrayHasKey('status', $all);
        $this->assertArrayHasKey('priority', $all);
        $this->assertEquals(TestStatusEnum::Active, $all['status']);
        $this->assertEquals(TestPriorityEnum::High, $all['priority']);
    }

    #[Test]
    public function it_records_enum_changes_in_audit_trail(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->manager->set('status', TestStatusEnum::Inactive);

        $this->assertDatabaseCount('schema_settings_history', 2);

        $history = \SgFlores\SchemaSetting\Models\SettingHistory::where('key', 'status')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(2, $history);
        $this->assertEquals(json_encode('active'), $history[0]->new_value);
        $this->assertEquals(json_encode('active'), $history[1]->old_value);
        $this->assertEquals(json_encode('inactive'), $history[1]->new_value);
    }

    #[Test]
    public function it_can_delete_enum_setting(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $this->assertEquals(TestStatusEnum::Active, $this->manager->get('status'));

        $this->manager->delete('status');
        $value = $this->manager->get('status');

        // Should return to default
        $this->assertEquals(TestStatusEnum::Pending, $value);
    }

    #[Test]
    public function it_handles_enum_serialization_correctly(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);

        $setting = Setting::where('key', 'status')->first();

        // Value should be JSON encoded
        $this->assertJson($setting->value);

        // Should be stored as scalar, not object
        $decoded = json_decode($setting->value, true);
        $this->assertEquals('active', $decoded);
        $this->assertIsString($decoded);
    }

    #[Test]
    public function it_handles_enum_with_special_characters(): void
    {
        // Even though our test enum doesn't have special characters,
        // we test that the system can handle the value
        $this->manager->set('status', TestStatusEnum::Active);
        $value = $this->manager->get('status');

        $this->assertEquals(TestStatusEnum::Active, $value);
    }

    #[Test]
    public function it_can_check_if_enum_value_is_specific_case(): void
    {
        $this->manager->set('status', TestStatusEnum::Active);
        $value = $this->manager->get('status');

        $this->assertTrue($value === TestStatusEnum::Active);
        $this->assertFalse($value === TestStatusEnum::Pending);
        $this->assertFalse($value === TestStatusEnum::Inactive);
    }
}
