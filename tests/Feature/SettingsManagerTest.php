<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Tests\TestCase;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Facades\Settings;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Exceptions\ReadonlySettingException;
use SgFlores\SchemaSetting\Exceptions\InvalidConfigurableException;
use Illuminate\Validation\ValidationException;

class SettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestGlobalSettings::class);
        $this->manager->register(TestUserSettings::class);
    }

    #[Test]
    public function it_can_register_a_configurable_class(): void
    {
        $schema = $this->manager->getSchema('global');

        $this->assertNotEmpty($schema);
        $this->assertArrayHasKey('site_name', $schema);
        $this->assertArrayHasKey('maintenance_mode', $schema);
    }

    #[Test]
    public function it_can_register_model_scoped_configurable(): void
    {
        $schema = $this->manager->getSchema(TestUser::class);

        $this->assertNotEmpty($schema);
        $this->assertArrayHasKey('theme', $schema);
        $this->assertArrayHasKey('notifications_enabled', $schema);
    }

    #[Test]
    public function it_throws_exception_for_non_configurable_class(): void
    {
        $this->expectException(InvalidConfigurableException::class);
        $this->expectExceptionMessage('must implement SgFlores\SchemaSetting\Contracts\ConfigurableInterface');

        $this->manager->register(\stdClass::class);
    }

    #[Test]
    public function it_returns_default_value_when_no_database_record_exists(): void
    {
        $value = $this->manager->get('site_name');

        $this->assertEquals('Test Site', $value);
    }

    #[Test]
    public function it_can_set_and_get_string_value(): void
    {
        $this->manager->set('site_name', 'My New Site');
        $value = $this->manager->get('site_name');

        $this->assertEquals('My New Site', $value);
    }

    #[Test]
    public function it_can_set_and_get_boolean_value(): void
    {
        $this->manager->set('maintenance_mode', true);
        $value = $this->manager->get('maintenance_mode');

        $this->assertIsBool($value);
        $this->assertTrue($value);

        $this->manager->set('maintenance_mode', false);
        $value = $this->manager->get('maintenance_mode');

        $this->assertFalse($value);
    }

    #[Test]
    public function it_casts_boolean_values_correctly(): void
    {
        // Test that stored "1" becomes boolean true
        Setting::create([
            'key' => 'maintenance_mode',
            'value' => json_encode(1),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('maintenance_mode');

        $this->assertIsBool($value);
        $this->assertTrue($value);
    }

    #[Test]
    public function it_casts_integer_values_correctly(): void
    {
        $this->manager->set('max_users', 500);
        $value = $this->manager->get('max_users');

        $this->assertIsInt($value);
        $this->assertEquals(500, $value);
    }

    #[Test]
    public function it_casts_float_values_correctly(): void
    {
        $this->manager->set('tax_rate', 0.25);
        $value = $this->manager->get('tax_rate');

        $this->assertIsFloat($value);
        $this->assertEquals(0.25, $value);
    }

    #[Test]
    public function it_casts_array_values_correctly(): void
    {
        $ips = ['192.168.1.1', '192.168.1.2', '10.0.0.1'];
        $this->manager->set('allowed_ips', $ips);
        $value = $this->manager->get('allowed_ips');

        $this->assertIsArray($value);
        $this->assertEquals($ips, $value);
    }

    #[Test]
    public function it_casts_json_values_correctly(): void
    {
        $metadata = ['version' => '2.0.0', 'environment' => 'testing'];
        $this->manager->set('metadata', $metadata);
        $value = $this->manager->get('metadata');

        $this->assertIsArray($value);
        $this->assertEquals($metadata, $value);
    }

    #[Test]
    public function it_validates_values_against_rules(): void
    {
        $this->expectException(ValidationException::class);

        // site_name has min:3 rule
        $this->manager->set('site_name', 'AB');
    }

    #[Test]
    public function it_validates_integer_min_max(): void
    {
        $this->expectException(ValidationException::class);

        // max_users has max:10000 rule
        $this->manager->set('max_users', 20000);
    }

    #[Test]
    public function it_validates_options(): void
    {
        $this->expectException(ValidationException::class);

        // language has options: ['en', 'es', 'fr', 'de']
        $this->manager->set('language', 'invalid_language');
    }

    #[Test]
    public function it_allows_valid_options(): void
    {
        $this->manager->set('language', 'es');
        $value = $this->manager->get('language');

        $this->assertEquals('es', $value);
    }

    #[Test]
    public function it_can_delete_a_setting(): void
    {
        $this->manager->set('site_name', 'To Be Deleted');
        $this->assertEquals('To Be Deleted', $this->manager->get('site_name'));

        $this->manager->delete('site_name');

        // Should return default value after deletion
        $this->assertEquals('Test Site', $this->manager->get('site_name'));
    }

    #[Test]
    public function it_cannot_modify_readonly_settings(): void
    {
        $this->expectException(ReadonlySettingException::class);
        $this->expectExceptionMessage('readonly');

        $this->manager->set('installation_date', now()->toDateTimeString());
    }

    #[Test]
    public function it_cannot_delete_readonly_settings(): void
    {
        $this->expectException(ReadonlySettingException::class);
        $this->expectExceptionMessage('readonly');

        $this->manager->delete('installation_date');
    }

    #[Test]
    public function it_throws_exception_for_undefined_key(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->expectExceptionMessage("Setting key 'non_existent' not found");

        $this->manager->get('non_existent');
    }

    #[Test]
    public function it_can_get_multiple_settings(): void
    {
        $this->manager->set('site_name', 'Multi Test');
        $this->manager->set('maintenance_mode', true);

        $values = $this->manager->getMultiple(['site_name', 'maintenance_mode']);

        $this->assertEquals('Multi Test', $values['site_name']);
        $this->assertTrue($values['maintenance_mode']);
    }

    #[Test]
    public function it_can_set_multiple_settings(): void
    {
        $this->manager->setMultiple([
            'site_name' => 'Bulk Update',
            'maintenance_mode' => true,
            'max_users' => 200,
        ]);

        $this->assertEquals('Bulk Update', $this->manager->get('site_name'));
        $this->assertTrue($this->manager->get('maintenance_mode'));
        $this->assertEquals(200, $this->manager->get('max_users'));
    }

    #[Test]
    public function it_can_get_all_settings_for_global_scope(): void
    {
        $this->manager->set('site_name', 'All Settings');
        $all = $this->manager->all();

        $this->assertArrayHasKey('site_name', $all);
        $this->assertArrayHasKey('maintenance_mode', $all);
        $this->assertArrayHasKey('max_users', $all);
        $this->assertEquals('All Settings', $all['site_name']);
    }

    #[Test]
    public function it_checks_if_setting_exists_in_schema(): void
    {
        $this->assertTrue($this->manager->has('site_name'));
        $this->assertTrue($this->manager->has('maintenance_mode'));
        $this->assertFalse($this->manager->has('non_existent_key'));
    }

    #[Test]
    public function it_can_get_or_fail_for_existing_setting(): void
    {
        $this->manager->set('site_name', 'Test Value');
        $value = $this->manager->getOrFail('site_name');

        $this->assertEquals('Test Value', $value);
    }

    #[Test]
    public function it_throws_exception_on_get_or_fail_for_non_existent_setting(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->manager->getOrFail('non_existent_key');
    }

    #[Test]
    public function it_can_get_schema_for_specific_scope(): void
    {
        $schema = $this->manager->getSchema('global');

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('site_name', $schema);
    }

    #[Test]
    public function it_can_get_all_schemas(): void
    {
        $schemas = $this->manager->getSchema();

        $this->assertArrayHasKey('global', $schemas);
        $this->assertArrayHasKey(TestUser::class, $schemas);
    }

    #[Test]
    public function it_works_with_facade(): void
    {
        Settings::set('site_name', 'Facade Test');
        $value = Settings::get('site_name');

        $this->assertEquals('Facade Test', $value);
    }

    #[Test]
    public function it_stores_settings_in_database(): void
    {
        $this->manager->set('site_name', 'Database Test');

        $this->assertDatabaseHas('schema_settings', [
            'key' => 'site_name',
            'reference_type' => null,
            'reference_id' => null,
        ]);
    }

    #[Test]
    public function it_updates_existing_setting(): void
    {
        $this->manager->set('site_name', 'First Value');
        $this->manager->set('site_name', 'Updated Value');

        $count = Setting::where('key', 'site_name')->count();

        $this->assertEquals(1, $count);
        $this->assertEquals('Updated Value', $this->manager->get('site_name'));
    }

    #[Test]
    public function it_deletes_from_database(): void
    {
        $this->manager->set('site_name', 'To Delete');
        $this->manager->delete('site_name');

        $this->assertDatabaseMissing('schema_settings', [
            'key' => 'site_name',
        ]);
    }
}
