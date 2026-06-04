<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Exceptions\ReadonlySettingException;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class BatchOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestGlobalSettings::class);
        $this->manager->register(TestUserSettings::class);

        $this->user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
        ]);
    }

    // ==================== getMultiple() Tests ====================

    #[Test]
    public function get_multiple_returns_all_requested_settings(): void
    {
        $this->manager->set('site_name', 'Test Site');
        $this->manager->set('maintenance_mode', true);
        $this->manager->set('max_users', 200);

        $values = $this->manager->getMultiple(['site_name', 'maintenance_mode', 'max_users']);

        $this->assertCount(3, $values);
        $this->assertEquals('Test Site', $values['site_name']);
        $this->assertTrue($values['maintenance_mode']);
        $this->assertEquals(200, $values['max_users']);
    }

    #[Test]
    public function get_multiple_returns_defaults_for_unset_values(): void
    {
        $values = $this->manager->getMultiple(['site_name', 'maintenance_mode', 'max_users']);

        $this->assertCount(3, $values);
        $this->assertEquals('Test Site', $values['site_name']);
        $this->assertFalse($values['maintenance_mode']);
        $this->assertEquals(100, $values['max_users']);
    }

    #[Test]
    public function get_multiple_mixes_set_and_default_values(): void
    {
        $this->manager->set('site_name', 'Custom Site');
        // maintenance_mode not set - should use default

        $values = $this->manager->getMultiple(['site_name', 'maintenance_mode']);

        $this->assertEquals('Custom Site', $values['site_name']);
        $this->assertFalse($values['maintenance_mode']); // default
    }

    #[Test]
    public function get_multiple_uses_cache_when_available(): void
    {
        $this->manager->set('site_name', 'Cached Site');
        $this->manager->set('max_users', 150);

        // First call - caches values
        $this->manager->getMultiple(['site_name', 'max_users']);

        // Modify database directly
        Setting::where('key', 'site_name')->update(['value' => json_encode('Modified Site')]);

        // Second call - should use cache
        $values = $this->manager->getMultiple(['site_name', 'max_users']);

        $this->assertEquals('Cached Site', $values['site_name']); // Cached value
    }

    #[Test]
    public function get_multiple_mixes_cached_and_uncached_values(): void
    {
        $this->manager->set('site_name', 'Site Name');

        // Cache site_name
        $this->manager->get('site_name');

        // Set max_users but don't cache it
        $this->manager->set('max_users', 150);
        Cache::forget('test_settings_global:null:max_users');

        // Get both - one cached, one from DB
        $values = $this->manager->getMultiple(['site_name', 'max_users']);

        $this->assertEquals('Site Name', $values['site_name']);
        $this->assertEquals(150, $values['max_users']);
    }

    #[Test]
    public function get_multiple_throws_exception_for_invalid_key(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->manager->getMultiple(['site_name', 'non_existent_key']);
    }

    #[Test]
    public function get_multiple_works_with_empty_array(): void
    {
        $values = $this->manager->getMultiple([]);

        $this->assertIsArray($values);
        $this->assertEmpty($values);
    }

    #[Test]
    public function get_multiple_works_with_model_scoped_settings(): void
    {
        $this->user->setSetting('theme', 'dark');
        $this->user->setSetting('notifications_enabled', false);

        $values = $this->manager->getMultiple(['theme', 'notifications_enabled'], $this->user);

        $this->assertCount(2, $values);
        $this->assertEquals('dark', $values['theme']);
        $this->assertFalse($values['notifications_enabled']);
    }

    #[Test]
    public function get_multiple_uses_single_database_query(): void
    {
        $this->manager->set('site_name', 'Test');
        $this->manager->set('maintenance_mode', true);
        $this->manager->set('max_users', 200);

        // Clear cache to force DB query
        Cache::flush();

        // Count queries
        DB::enableQueryLog();

        $this->manager->getMultiple(['site_name', 'maintenance_mode', 'max_users']);

        $queries = DB::getQueryLog();

        // Should be 1 query with whereIn, not 3 separate queries
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('in', strtolower($queries[0]['query']));
    }

    // ==================== setMultiple() Tests ====================

    #[Test]
    public function set_multiple_sets_all_values(): void
    {
        $result = $this->manager->setMultiple([
            'site_name' => 'Batch Site',
            'maintenance_mode' => true,
            'max_users' => 250,
        ]);

        $this->assertTrue($result);
        $this->assertEquals('Batch Site', $this->manager->get('site_name'));
        $this->assertTrue($this->manager->get('maintenance_mode'));
        $this->assertEquals(250, $this->manager->get('max_users'));
    }

    #[Test]
    public function set_multiple_is_atomic_on_validation_error(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->manager->setMultiple([
                'site_name' => 'Valid Site',
                'max_users' => 99999, // Invalid - exceeds max:10000
            ]);
        } catch (ValidationException $e) {
            // Verify NEITHER value was saved (transaction rollback)
            $this->assertEquals('Test Site', $this->manager->get('site_name')); // Still default
            $this->assertEquals(100, $this->manager->get('max_users')); // Still default
            throw $e;
        }
    }

    #[Test]
    public function set_multiple_rolls_back_on_readonly_exception(): void
    {
        $this->expectException(ReadonlySettingException::class);

        try {
            $this->manager->setMultiple([
                'site_name' => 'Valid Site',
                'installation_date' => '2024-10-14', // readonly
            ]);
        } catch (ReadonlySettingException $e) {
            // Verify first value was NOT saved
            $this->assertEquals('Test Site', $this->manager->get('site_name'));
            throw $e;
        }
    }

    #[Test]
    public function set_multiple_rolls_back_on_setting_not_found(): void
    {
        $this->expectException(SettingNotFoundException::class);

        try {
            $this->manager->setMultiple([
                'site_name' => 'Valid Site',
                'non_existent_key' => 'value',
            ]);
        } catch (SettingNotFoundException $e) {
            // Verify first value was NOT saved
            $this->assertEquals('Test Site', $this->manager->get('site_name'));
            throw $e;
        }
    }

    #[Test]
    public function set_multiple_works_with_empty_array(): void
    {
        $result = $this->manager->setMultiple([]);

        $this->assertTrue($result);
    }

    #[Test]
    public function set_multiple_invalidates_all_caches(): void
    {
        $this->manager->set('site_name', 'Original');
        $this->manager->set('max_users', 100);

        // Cache both
        $this->manager->get('site_name');
        $this->manager->get('max_users');

        // Update both
        $this->manager->setMultiple([
            'site_name' => 'Updated',
            'max_users' => 200,
        ]);

        // Should get updated values, not cached
        $this->assertEquals('Updated', $this->manager->get('site_name'));
        $this->assertEquals(200, $this->manager->get('max_users'));
    }

    #[Test]
    public function set_multiple_creates_audit_trail_for_all_changes(): void
    {
        $this->manager->setMultiple([
            'site_name' => 'Batch Site',
            'maintenance_mode' => true,
            'max_users' => 250,
        ]);

        $this->assertDatabaseCount('schema_settings_history', 3);
    }

    #[Test]
    public function set_multiple_works_with_model_scoped_settings(): void
    {
        $result = $this->manager->setMultiple([
            'theme' => 'dark',
            'notifications_enabled' => false,
            'items_per_page' => 50,
        ], $this->user);

        $this->assertTrue($result);
        $this->assertEquals('dark', $this->manager->get('theme', $this->user));
        $this->assertFalse($this->manager->get('notifications_enabled', $this->user));
        $this->assertEquals(50, $this->manager->get('items_per_page', $this->user));
    }

    #[Test]
    public function set_multiple_validates_all_values_before_saving(): void
    {
        $this->expectException(ValidationException::class);

        $this->manager->setMultiple([
            'site_name' => 'Good Name',
            'max_users' => 'not_a_number', // Will fail validation
            'maintenance_mode' => true,
        ]);
    }

    #[Test]
    public function set_multiple_with_mixed_data_types(): void
    {
        $this->manager->setMultiple([
            'site_name' => 'String Value',      // string
            'maintenance_mode' => true,          // boolean
            'max_users' => 300,                  // integer
            'tax_rate' => 0.25,                  // float
            'allowed_ips' => ['192.168.1.1'],   // array
            'metadata' => ['key' => 'value'],    // json
        ]);

        $this->assertEquals('String Value', $this->manager->get('site_name'));
        $this->assertTrue($this->manager->get('maintenance_mode'));
        $this->assertEquals(300, $this->manager->get('max_users'));
        $this->assertEquals(0.25, $this->manager->get('tax_rate'));
        $this->assertEquals(['192.168.1.1'], $this->manager->get('allowed_ips'));
        $this->assertEquals(['key' => 'value'], $this->manager->get('metadata'));
    }

    // ==================== all() Tests ====================

    #[Test]
    public function all_returns_all_registered_settings(): void
    {
        $all = $this->manager->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('site_name', $all);
        $this->assertArrayHasKey('maintenance_mode', $all);
        $this->assertArrayHasKey('max_users', $all);
        $this->assertArrayHasKey('tax_rate', $all);
        $this->assertArrayHasKey('allowed_ips', $all);
        $this->assertArrayHasKey('metadata', $all);
    }

    #[Test]
    public function all_returns_defaults_for_unset_values(): void
    {
        $all = $this->manager->all();

        $this->assertEquals('Test Site', $all['site_name']);
        $this->assertFalse($all['maintenance_mode']);
        $this->assertEquals(100, $all['max_users']);
    }

    #[Test]
    public function all_mixes_set_and_default_values(): void
    {
        $this->manager->set('site_name', 'Custom Site');
        $this->manager->set('max_users', 200);
        // Other settings not set

        $all = $this->manager->all();

        $this->assertEquals('Custom Site', $all['site_name']); // Custom
        $this->assertEquals(200, $all['max_users']); // Custom
        $this->assertFalse($all['maintenance_mode']); // Default
        $this->assertEquals(0.15, $all['tax_rate']); // Default
    }

    #[Test]
    public function all_uses_single_database_query(): void
    {
        $this->manager->set('site_name', 'Test');
        $this->manager->set('max_users', 200);

        Cache::flush();

        DB::enableQueryLog();

        $this->manager->all();

        $queries = DB::getQueryLog();

        // Should be 1 query with whereIn
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('in', strtolower($queries[0]['query']));
    }

    #[Test]
    public function all_returns_properly_cast_values(): void
    {
        $this->manager->set('site_name', 'Test');
        $this->manager->set('maintenance_mode', true);
        $this->manager->set('max_users', 200);
        $this->manager->set('tax_rate', 0.25);

        $all = $this->manager->all();

        $this->assertIsString($all['site_name']);
        $this->assertIsBool($all['maintenance_mode']);
        $this->assertIsInt($all['max_users']);
        $this->assertIsFloat($all['tax_rate']);
    }

    #[Test]
    public function all_returns_empty_for_unregistered_scope(): void
    {
        $all = $this->manager->all();

        // Global settings should exist
        $this->assertNotEmpty($all);

        // Create manager without registering anything
        $newManager = new SettingsManager;
        $result = $newManager->all();

        $this->assertEmpty($result);
    }

    #[Test]
    public function all_works_with_model_scoped_settings(): void
    {
        $this->user->setSetting('theme', 'dark');
        $this->user->setSetting('notifications_enabled', false);

        $all = $this->manager->all($this->user);

        $this->assertArrayHasKey('theme', $all);
        $this->assertArrayHasKey('notifications_enabled', $all);
        $this->assertEquals('dark', $all['theme']);
        $this->assertFalse($all['notifications_enabled']);
    }

    #[Test]
    public function all_uses_cache_for_previously_fetched_settings(): void
    {
        $this->manager->set('site_name', 'Original');

        // Cache it
        $this->manager->get('site_name');

        // Modify DB
        Setting::where('key', 'site_name')->update(['value' => json_encode('Modified')]);

        // all() should use cached value
        $all = $this->manager->all();

        $this->assertEquals('Original', $all['site_name']);
    }

    #[Test]
    public function all_caches_newly_fetched_values(): void
    {
        $this->manager->set('site_name', 'Test Site');

        Cache::flush();

        // First call - should cache
        $this->manager->all();

        // Check cache exists
        $cacheKey = 'test_settings_global:null:site_name';
        $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function all_isolates_between_different_models(): void
    {
        $user2 = TestUser::create([
            'name' => 'User Two',
            'email' => 'user2@test.com',
        ]);

        $this->user->setSetting('theme', 'dark');
        $user2->setSetting('theme', 'light');

        $user1All = $this->manager->all($this->user);
        $user2All = $this->manager->all($user2);

        $this->assertEquals('dark', $user1All['theme']);
        $this->assertEquals('light', $user2All['theme']);
    }

    // ==================== Performance Tests ====================

    #[Test]
    public function get_multiple_is_faster_than_individual_gets(): void
    {
        $keys = ['site_name', 'maintenance_mode', 'tax_rate', 'language'];

        // Set valid values for each type
        $this->manager->set('site_name', 'Valid Site Name');
        $this->manager->set('maintenance_mode', true);
        $this->manager->set('tax_rate', 0.20);
        $this->manager->set('language', 'en');

        Cache::flush();

        // Individual gets
        DB::enableQueryLog();
        foreach ($keys as $key) {
            try {
                $this->manager->get($key);
            } catch (\Exception $e) {
                // Some may fail, that's OK
            }
        }
        $individualQueries = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Clear cache again
        Cache::flush();

        // Batch get
        $this->manager->getMultiple($keys);
        $batchQueries = count(DB::getQueryLog());

        // Batch should use fewer queries (1 vs 5)
        $this->assertLessThan($individualQueries, $batchQueries);
    }

    #[Test]
    public function set_multiple_with_large_batch(): void
    {
        $settings = [];
        for ($i = 0; $i < 5; $i++) {
            $settings['metadata'] = ['index' => $i];
        }

        // Final batch
        $result = $this->manager->setMultiple([
            'site_name' => 'Batch Site',
            'maintenance_mode' => true,
            'max_users' => 300,
            'tax_rate' => 0.20,
            'language' => 'es',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseCount('schema_settings', 5);
    }

    #[Test]
    public function all_handles_large_schema(): void
    {
        // Set multiple values
        $this->manager->setMultiple([
            'site_name' => 'Test',
            'maintenance_mode' => true,
            'max_users' => 200,
            'tax_rate' => 0.25,
            'language' => 'en',
            'metadata' => ['test' => 'data'],
        ]);

        $all = $this->manager->all();

        $this->assertGreaterThanOrEqual(6, count($all));
        $this->assertIsArray($all);
    }

    // ==================== Edge Cases ====================

    #[Test]
    public function get_multiple_handles_duplicate_keys(): void
    {
        $this->manager->set('site_name', 'Test Site');

        $values = $this->manager->getMultiple(['site_name', 'site_name', 'site_name']);

        // Array keys are unique, so duplicates collapse to single key
        $this->assertCount(1, $values);
        $this->assertEquals('Test Site', $values['site_name']);
    }

    #[Test]
    public function set_multiple_handles_same_key_twice(): void
    {
        // Last value should win
        $result = $this->manager->setMultiple([
            'site_name' => 'First Value',
            'site_name' => 'Second Value',
        ]);

        $this->assertTrue($result);
        $this->assertEquals('Second Value', $this->manager->get('site_name'));
    }

    #[Test]
    public function batch_operations_work_with_encrypted_fields(): void
    {
        $this->manager->setMultiple([
            'api_key' => 'super_secret_key_1234567890abcdef',
            'site_name' => 'Test Site',
        ]);

        $values = $this->manager->getMultiple(['api_key', 'site_name']);

        $this->assertEquals('super_secret_key_1234567890abcdef', $values['api_key']);
        $this->assertEquals('Test Site', $values['site_name']);
    }

    #[Test]
    public function all_includes_encrypted_values_decrypted(): void
    {
        $this->manager->set('api_key', 'super_secret_key_1234567890abcdef');

        $all = $this->manager->all();

        $this->assertEquals('super_secret_key_1234567890abcdef', $all['api_key']);
    }
}
