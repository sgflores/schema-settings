<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Facades\Settings;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class SettingsApiTest extends TestCase
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
    public function it_can_retrieve_single_setting_schema_with_value_via_api(): void
    {
        Settings::set('site_name', 'API Test Site');

        $response = $this->getJson('/api/schema-settings?key=site_name');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'site_name' => [
                        'key' => 'site_name',
                        'type' => 'string',
                        'default' => 'Test Site',
                        'value' => 'API Test Site',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'site_name' => [
                        'key',
                        'type',
                        'default',
                        'rules',
                        'group',
                        'label',
                        'description',
                        'encrypted',
                        'readonly',
                        'enumClass',
                        'options',
                        'value',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_can_retrieve_multiple_settings_schema_with_values_via_api(): void
    {
        Settings::set('site_name', 'Multi Test Site');
        Settings::set('maintenance_mode', true);

        $response = $this->getJson('/api/schema-settings?keys[]=site_name&keys[]=maintenance_mode');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'site_name' => [
                        'key',
                        'type',
                        'default',
                        'value',
                    ],
                    'maintenance_mode' => [
                        'key',
                        'type',
                        'default',
                        'value',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('Multi Test Site', $data['site_name']['value']);
        $this->assertTrue($data['maintenance_mode']['value']);
    }

    #[Test]
    public function it_returns_all_settings_when_no_keys_provided(): void
    {
        Settings::set('site_name', 'All Settings Test');

        $response = $this->getJson('/api/schema-settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'site_name' => [],
                    'maintenance_mode' => [],
                ],
            ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('site_name', $data);
        $this->assertArrayHasKey('maintenance_mode', $data);
        $this->assertEquals('All Settings Test', $data['site_name']['value']);
    }

    #[Test]
    public function it_returns_default_value_when_no_persisted_value_exists(): void
    {
        $response = $this->getJson('/api/schema-settings?key=site_name');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'site_name' => [
                        'value' => 'Test Site',
                        'default' => 'Test Site',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_handles_nonexistent_setting_key(): void
    {
        $response = $this->getJson('/api/schema-settings?key=nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'error',
            ]);
    }

    #[Test]
    public function it_validates_request_parameters_for_single_key(): void
    {
        $response = $this->getJson('/api/schema-settings?key='.str_repeat('a', 300));

        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_request_parameters_for_multiple_keys(): void
    {
        $keys = array_fill(0, 60, 'site_name'); // More than 50 limit

        $response = $this->getJson('/api/schema-settings?'.http_build_query(['keys' => $keys]));

        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_array_parameter_type(): void
    {
        $response = $this->getJson('/api/schema-settings?keys=not_an_array');

        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_non_string_keys_in_array(): void
    {
        // Test with numeric key (which will be converted to string in query params)
        // Since query params are always strings, we'll test max length instead
        $response = $this->getJson('/api/schema-settings?keys[]=site_name&keys[]='.str_repeat('a', 300));

        $response->assertStatus(422);
    }

    #[Test]
    public function it_handles_model_scoped_setting_key_correctly(): void
    {
        // Test that requesting a model-scoped setting key without model context
        // returns 404 since it's not in the global schema
        $response = $this->getJson('/api/schema-settings?key=theme');

        // Theme is a model-scoped setting (TestUserSettings), not global
        // So it should return 404 when requested without model context
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'error',
            ]);
    }

    #[Test]
    public function it_returns_boolean_type_correctly(): void
    {
        Settings::set('maintenance_mode', true);

        $response = $this->getJson('/api/schema-settings?key=maintenance_mode');

        $response->assertStatus(200);

        $data = $response->json('data.maintenance_mode');
        $this->assertEquals('boolean', $data['type']);
        $this->assertIsBool($data['value']);
        $this->assertTrue($data['value']);
    }

    #[Test]
    public function it_handles_mixed_valid_and_invalid_keys(): void
    {
        $response = $this->getJson('/api/schema-settings?keys[]=site_name&keys[]=nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'error',
            ]);
    }

    #[Test]
    public function it_filters_empty_keys_from_array(): void
    {
        $response = $this->getJson('/api/schema-settings?keys[]=site_name&keys[]=&keys[]=maintenance_mode');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('site_name', $data);
        $this->assertArrayHasKey('maintenance_mode', $data);
    }
}
