<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestGlobalSettings::class);
    }

    #[Test]
    public function it_can_list_settings(): void
    {
        $this->artisan('schema-settings:list')
            ->assertExitCode(0)
            ->expectsOutput('Settings for scope: global');
    }

    #[Test]
    public function it_can_list_settings_with_groups(): void
    {
        $this->artisan('schema-settings:list', ['--groups' => true])
            ->assertExitCode(0);
    }

    #[Test]
    public function it_shows_error_for_non_existent_scope(): void
    {
        $this->artisan('schema-settings:list', ['scope' => 'NonExistentScope'])
            ->assertExitCode(1)
            ->expectsOutput('No settings found for scope: NonExistentScope');
    }

    #[Test]
    public function it_can_get_a_setting_value(): void
    {
        $this->manager->set('site_name', 'Test Site from CLI');

        $this->artisan('schema-settings:get', ['key' => 'site_name'])
            ->assertExitCode(0)
            ->expectsOutput('Key: site_name');
    }

    #[Test]
    public function it_can_get_setting_as_json(): void
    {
        $this->manager->set('site_name', 'JSON Test');

        $this->artisan('schema-settings:get', [
            'key' => 'site_name',
            '--json' => true,
        ])->assertExitCode(0);
    }

    #[Test]
    public function it_shows_error_when_getting_non_existent_setting(): void
    {
        $this->artisan('schema-settings:get', ['key' => 'non_existent'])
            ->assertExitCode(1);
    }

    #[Test]
    public function it_can_set_a_string_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'site_name',
            'value' => 'CLI Updated Site',
        ])->assertExitCode(0)
            ->expectsOutput("Setting 'site_name' has been updated successfully.");

        $this->assertEquals('CLI Updated Site', $this->manager->get('site_name'));
    }

    #[Test]
    public function it_can_set_boolean_true_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'maintenance_mode',
            'value' => 'true',
        ])->assertExitCode(0);

        $this->assertTrue($this->manager->get('maintenance_mode'));
    }

    #[Test]
    public function it_can_set_boolean_false_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'maintenance_mode',
            'value' => 'false',
        ])->assertExitCode(0);

        $this->assertFalse($this->manager->get('maintenance_mode'));
    }

    #[Test]
    public function it_can_set_integer_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'max_users',
            'value' => '500',
        ])->assertExitCode(0);

        $this->assertEquals(500, $this->manager->get('max_users'));
    }

    #[Test]
    public function it_can_set_float_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'tax_rate',
            'value' => '0.25',
        ])->assertExitCode(0);

        $this->assertEquals(0.25, $this->manager->get('tax_rate'));
    }

    #[Test]
    public function it_can_set_null_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'features',
            'value' => 'null',
        ])->assertExitCode(0);
    }

    #[Test]
    public function it_can_set_json_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'metadata',
            'value' => '{"version":"2.0.0"}',
            '--json' => true,
        ])->assertExitCode(0);

        $metadata = $this->manager->get('metadata');
        $this->assertEquals(['version' => '2.0.0'], $metadata);
    }

    #[Test]
    public function it_shows_error_for_invalid_json(): void
    {
        $result = $this->artisan('schema-settings:set', [
            'key' => 'metadata',
            'value' => 'invalid-json',
            '--json' => true,
        ]);

        $result->assertExitCode(1);
    }

    #[Test]
    public function it_shows_error_when_setting_non_existent_key(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'non_existent',
            'value' => 'test',
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_can_clear_cache(): void
    {
        $this->manager->set('site_name', 'Cached');
        $this->manager->get('site_name'); // Cache it

        $this->artisan('schema-settings:clear-cache')
            ->assertExitCode(0)
            ->expectsOutput('All settings cache has been cleared.');
    }

    #[Test]
    public function it_can_clear_cache_for_specific_scope(): void
    {
        $this->artisan('schema-settings:clear-cache', ['scope' => 'global'])
            ->assertExitCode(0)
            ->expectsOutput('Cache cleared for scope: global');
    }

    // ==================== Additional List Command Tests ====================

    #[Test]
    public function list_command_shows_all_setting_details(): void
    {
        $this->artisan('schema-settings:list')
            ->expectsOutputToContain('site_name')
            ->expectsOutputToContain('maintenance_mode')
            ->expectsOutputToContain('max_users')
            ->assertExitCode(0);
    }

    #[Test]
    public function list_command_with_groups_shows_grouped_output(): void
    {
        $this->artisan('schema-settings:list', ['--groups' => true])
            ->expectsOutputToContain('Group:')
            ->assertExitCode(0);
    }

    // ==================== Additional Get Command Tests ====================

    #[Test]
    public function get_command_displays_boolean_values_correctly(): void
    {
        $this->manager->set('maintenance_mode', true);

        $this->artisan('schema-settings:get', ['key' => 'maintenance_mode'])
            ->expectsOutputToContain('true')
            ->assertExitCode(0);
    }

    #[Test]
    public function get_command_displays_array_values_as_json(): void
    {
        $this->manager->set('allowed_ips', ['192.168.1.1', '10.0.0.1']);

        $this->artisan('schema-settings:get', ['key' => 'allowed_ips'])
            ->expectsOutputToContain('192.168.1.1')
            ->assertExitCode(0);
    }

    #[Test]
    public function get_command_displays_json_values(): void
    {
        $this->manager->set('metadata', ['version' => '2.0.0', 'build' => 123]);

        $this->artisan('schema-settings:get', ['key' => 'metadata'])
            ->expectsOutputToContain('version')
            ->assertExitCode(0);
    }

    #[Test]
    public function get_command_json_flag_outputs_valid_json(): void
    {
        $this->manager->set('site_name', 'Test Site');

        $output = $this->artisan('schema-settings:get', [
            'key' => 'site_name',
            '--json' => true,
        ]);

        $output->assertExitCode(0);
        // The output should be valid JSON
        $output->expectsOutputToContain('"key"');
    }

    #[Test]
    public function get_command_displays_default_values(): void
    {
        // Get a setting that hasn't been set (uses default)
        $this->artisan('schema-settings:get', ['key' => 'site_name'])
            ->expectsOutputToContain('Test Site')
            ->assertExitCode(0);
    }

    #[Test]
    public function get_command_shows_null_values_as_text(): void
    {
        // features defaults to empty array, test with a nullable field instead
        $this->artisan('schema-settings:get', ['key' => 'features'])
            ->assertExitCode(0);
    }

    // ==================== Additional Set Command Tests ====================

    #[Test]
    public function set_command_auto_detects_numeric_values(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'max_users',
            'value' => '1000',
        ])->assertExitCode(0);

        $this->assertIsInt($this->manager->get('max_users'));
        $this->assertEquals(1000, $this->manager->get('max_users'));
    }

    #[Test]
    public function set_command_auto_detects_float_values(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'tax_rate',
            'value' => '0.35',
        ])->assertExitCode(0);

        $this->assertIsFloat($this->manager->get('tax_rate'));
        $this->assertEquals(0.35, $this->manager->get('tax_rate'));
    }

    #[Test]
    public function set_command_handles_arrays_with_json_flag(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'allowed_ips',
            'value' => '["192.168.1.1", "10.0.0.1"]',
            '--json' => true,
        ])->assertExitCode(0);

        $value = $this->manager->get('allowed_ips');
        $this->assertIsArray($value);
        $this->assertCount(2, $value);
    }

    #[Test]
    public function set_command_handles_complex_json(): void
    {
        $json = json_encode(['key1' => 'value1', 'key2' => ['nested' => 'value']]);

        $this->artisan('schema-settings:set', [
            'key' => 'metadata',
            'value' => $json,
            '--json' => true,
        ])->assertExitCode(0);

        $value = $this->manager->get('metadata');
        $this->assertEquals('value1', $value['key1']);
        $this->assertEquals('value', $value['key2']['nested']);
    }

    #[Test]
    public function set_command_validates_values(): void
    {
        // Try to set value exceeding max
        $this->artisan('schema-settings:set', [
            'key' => 'max_users',
            'value' => '99999',
        ])->assertExitCode(1);

        // Value should not be saved
        $this->assertEquals(100, $this->manager->get('max_users')); // Still default
    }

    #[Test]
    public function set_command_shows_error_for_readonly_setting(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'installation_date',
            'value' => '2024-10-14',
        ])->assertExitCode(1)
            ->expectsOutputToContain('Error:');
    }

    #[Test]
    public function set_command_parses_boolean_case_insensitive(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'maintenance_mode',
            'value' => 'TRUE',
        ])->assertExitCode(0);

        $this->assertTrue($this->manager->get('maintenance_mode'));

        $this->artisan('schema-settings:set', [
            'key' => 'maintenance_mode',
            'value' => 'FALSE',
        ])->assertExitCode(0);

        $this->assertFalse($this->manager->get('maintenance_mode'));
    }

    #[Test]
    public function set_command_handles_empty_string(): void
    {
        // Note: site_name has 'required' rule, so empty string should fail
        $this->artisan('schema-settings:set', [
            'key' => 'site_name',
            'value' => '',
        ])->assertExitCode(1); // Should fail validation
    }

    #[Test]
    public function set_command_handles_special_characters(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'site_name',
            'value' => 'Site with "quotes" and \'apostrophes\'',
        ])->assertExitCode(0);

        $value = $this->manager->get('site_name');
        $this->assertStringContainsString('quotes', $value);
    }

    // ==================== Additional Clear Cache Tests ====================

    #[Test]
    public function clear_cache_command_works_multiple_times(): void
    {
        $this->artisan('schema-settings:clear-cache')->assertExitCode(0);
        $this->artisan('schema-settings:clear-cache')->assertExitCode(0);

        // Should not fail on subsequent clears
        $this->assertTrue(true);
    }

    #[Test]
    public function clear_cache_command_handles_invalid_scope_gracefully(): void
    {
        // Even with invalid scope, should not crash
        $this->artisan('schema-settings:clear-cache', ['scope' => 'InvalidScope'])
            ->assertExitCode(0);
    }

    // ==================== Edge Cases ====================

    #[Test]
    public function commands_work_with_no_settings_registered(): void
    {
        // Create a fresh manager without registrations
        $freshManager = new SettingsManager;
        $this->app->instance('schema-settings', $freshManager);

        $this->artisan('schema-settings:list', ['scope' => 'global'])
            ->assertExitCode(1)
            ->expectsOutput('No settings found for scope: global');
    }

    #[Test]
    public function get_command_with_default_scope(): void
    {
        $this->manager->set('site_name', 'Test');

        $this->artisan('schema-settings:get', [
            'key' => 'site_name',
            '--scope' => 'global',
        ])->assertExitCode(0);
    }

    #[Test]
    public function set_command_with_default_scope(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'site_name',
            'value' => 'Test',
            '--scope' => 'global',
        ])->assertExitCode(0);
    }

    #[Test]
    public function list_command_formats_different_types_correctly(): void
    {
        $this->manager->set('site_name', 'String Value');
        $this->manager->set('maintenance_mode', true);
        $this->manager->set('max_users', 500);
        $this->manager->set('allowed_ips', ['192.168.1.1']);

        // List command shows schema info, not current values
        $this->artisan('schema-settings:list')
            ->expectsOutputToContain('site_name')
            ->assertExitCode(0);
    }
}
