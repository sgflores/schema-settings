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
