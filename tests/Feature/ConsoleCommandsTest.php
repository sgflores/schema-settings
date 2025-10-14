<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Tests\TestCase;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Manager\SettingsManager;

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
            '--json' => true
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
            'value' => 'CLI Updated Site'
        ])->assertExitCode(0)
          ->expectsOutput("Setting 'site_name' has been updated successfully.");

        $this->assertEquals('CLI Updated Site', $this->manager->get('site_name'));
    }

    #[Test]
    public function it_can_set_boolean_true_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'maintenance_mode',
            'value' => 'true'
        ])->assertExitCode(0);

        $this->assertTrue($this->manager->get('maintenance_mode'));
    }

    #[Test]
    public function it_can_set_boolean_false_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'maintenance_mode',
            'value' => 'false'
        ])->assertExitCode(0);

        $this->assertFalse($this->manager->get('maintenance_mode'));
    }

    #[Test]
    public function it_can_set_integer_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'max_users',
            'value' => '500'
        ])->assertExitCode(0);

        $this->assertEquals(500, $this->manager->get('max_users'));
    }

    #[Test]
    public function it_can_set_float_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'tax_rate',
            'value' => '0.25'
        ])->assertExitCode(0);

        $this->assertEquals(0.25, $this->manager->get('tax_rate'));
    }

    #[Test]
    public function it_can_set_null_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'features',
            'value' => 'null'
        ])->assertExitCode(0);
    }

    #[Test]
    public function it_can_set_json_value(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'metadata',
            'value' => '{"version":"2.0.0"}',
            '--json' => true
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
            '--json' => true
        ]);

        $result->assertExitCode(1);
    }

    #[Test]
    public function it_shows_error_when_setting_non_existent_key(): void
    {
        $this->artisan('schema-settings:set', [
            'key' => 'non_existent',
            'value' => 'test'
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
}

