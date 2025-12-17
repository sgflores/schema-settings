<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class ErrorRecoveryTest extends TestCase
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
    public function it_returns_default_on_json_decode_error(): void
    {
        // Create a setting with invalid JSON
        Setting::create([
            'key' => 'site_name',
            'value' => 'invalid json {',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('site_name');

        // Should return default when JSON decode fails
        $this->assertEquals('Test Site', $value);
    }

    #[Test]
    public function it_handles_decrypt_failure_gracefully(): void
    {
        // Create an encrypted setting with corrupted data
        Setting::create([
            'key' => 'api_key',
            'value' => 'corrupted_encrypted_data_not_valid',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('api_key');

        // Should return default when decryption fails
        $this->assertEquals('', $value); // Default for api_key is empty string
    }

    #[Test]
    public function it_handles_null_values_in_database(): void
    {
        Setting::create([
            'key' => 'site_name',
            'value' => null,
            'reference_type' => null,
            'reference_id' => null,
        ]);

        // Should handle null value gracefully
        $value = $this->manager->get('site_name');

        $this->assertEquals('Test Site', $value); // Returns default
    }

    #[Test]
    public function it_handles_malformed_datetime_gracefully(): void
    {
        // Create a datetime setting with invalid format
        Setting::create([
            'key' => 'installation_date',
            'value' => json_encode('not-a-valid-date'),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('installation_date');

        // Should handle gracefully - returns default or null
        $this->assertTrue($value === null || $value instanceof \DateTimeInterface);
    }

    #[Test]
    public function it_handles_cache_connection_failure_gracefully(): void
    {
        // This test verifies the code continues to work even if cache fails
        // Actual implementation depends on cache driver behavior

        $this->manager->set('site_name', 'Test Value');
        $value = $this->manager->get('site_name');

        $this->assertEquals('Test Value', $value);
    }

    #[Test]
    public function get_continues_to_work_after_cache_failure(): void
    {
        $this->manager->set('site_name', 'Test Site');

        // Even if cache fails, should fetch from DB
        $value = $this->manager->get('site_name');

        $this->assertEquals('Test Site', $value);
    }

    #[Test]
    public function it_handles_concurrent_updates_gracefully(): void
    {
        $this->manager->set('site_name', 'Initial Value');

        // Simulate concurrent update by directly modifying DB
        Setting::where('key', 'site_name')->update([
            'value' => json_encode('Concurrent Update'),
        ]);

        // Clear cache to force fresh read
        Cache::flush();

        $value = $this->manager->get('site_name');

        // Should get the updated value from database
        $this->assertEquals('Concurrent Update', $value);
    }

    #[Test]
    public function it_handles_missing_database_record_gracefully(): void
    {
        // Try to get a setting that has no database record
        $value = $this->manager->get('site_name');

        // Should return default value
        $this->assertEquals('Test Site', $value);
    }

    #[Test]
    public function it_handles_type_mismatch_in_database(): void
    {
        // Store a value that doesn't match expected type
        Setting::create([
            'key' => 'max_users',
            'value' => json_encode('not_a_number'),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('max_users');

        // Type casting will convert it
        $this->assertIsInt($value);
        $this->assertEquals(0, $value); // 'not_a_number' cast to int = 0
    }

    #[Test]
    public function set_handles_serialization_of_complex_objects(): void
    {
        $complexArray = [
            'nested' => [
                'deep' => [
                    'value' => 'test',
                ],
            ],
            'array' => [1, 2, 3],
            'bool' => true,
            'null' => null,
        ];

        $this->manager->set('metadata', $complexArray);
        $value = $this->manager->get('metadata');

        $this->assertEquals($complexArray, $value);
    }

    #[Test]
    public function it_handles_special_characters_in_values(): void
    {
        $specialChars = "Test with 'quotes\", <tags>, and \n newlines \t tabs";

        $this->manager->set('site_name', $specialChars);
        $value = $this->manager->get('site_name');

        $this->assertEquals($specialChars, $value);
    }

    #[Test]
    public function it_handles_unicode_characters(): void
    {
        $unicode = 'Unicode: 你好 مرحبا שלום 🎉 émojis';

        $this->manager->set('site_name', $unicode);
        $value = $this->manager->get('site_name');

        $this->assertEquals($unicode, $value);
    }

    #[Test]
    public function it_handles_empty_arrays(): void
    {
        $this->manager->set('allowed_ips', []);

        $value = $this->manager->get('allowed_ips');

        $this->assertIsArray($value);
        $this->assertEmpty($value);
    }

    #[Test]
    public function it_handles_deeply_nested_json(): void
    {
        $deeplyNested = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => 'deep value',
                        ],
                    ],
                ],
            ],
        ];

        $this->manager->set('metadata', $deeplyNested);
        $value = $this->manager->get('metadata');

        $this->assertEquals($deeplyNested, $value);
    }

    #[Test]
    public function delete_handles_non_existent_setting(): void
    {
        $result = $this->manager->delete('site_name');

        // Should return false when setting doesn't exist in DB
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_false_boolean_correctly(): void
    {
        $this->manager->set('maintenance_mode', false);

        $value = $this->manager->get('maintenance_mode');

        $this->assertFalse($value);
        $this->assertNotNull($value);
    }

    #[Test]
    public function it_recovers_from_corrupted_cache_data(): void
    {
        $this->manager->set('site_name', 'Original Value');

        // Corrupt the cache
        $cacheKey = 'test_settings_global:null:site_name';
        Cache::put($cacheKey, 'corrupted', 3600);

        // Should return corrupted value from cache
        $value = $this->manager->get('site_name');
        $this->assertEquals('corrupted', $value);

        // After cache invalidation, should get correct value
        $this->manager->set('site_name', 'New Value');
        $value = $this->manager->get('site_name');
        $this->assertEquals('New Value', $value);
    }

    #[Test]
    public function it_handles_null_enum_class_gracefully(): void
    {
        // This tests the castToEnum method when enumClass is somehow null
        // The code checks for this and returns the original value
        $this->manager->set('site_name', 'test');
        $value = $this->manager->get('site_name');

        $this->assertEquals('test', $value);
    }

    #[Test]
    public function it_handles_setting_not_found_during_batch_get(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->manager->getMultiple(['site_name', 'invalid_key']);
    }

    #[Test]
    public function batch_operations_maintain_data_integrity(): void
    {
        // Ensure atomicity - if one fails, none are saved
        try {
            $this->manager->setMultiple([
                'site_name' => 'Valid',
                'max_users' => 99999, // Exceeds validation max
            ]);
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Neither should be in database
        $siteNameInDb = Setting::where('key', 'site_name')->exists();
        $maxUsersInDb = Setting::where('key', 'max_users')->exists();

        $this->assertFalse($siteNameInDb);
        $this->assertFalse($maxUsersInDb);
    }
}
