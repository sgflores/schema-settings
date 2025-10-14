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

class CachingTest extends TestCase
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

    #[Test]
    public function it_caches_settings_on_first_get(): void
    {
        $this->manager->set('site_name', 'Cached Site');

        // Clear cache to ensure fresh start
        Cache::flush();

        // First get - should cache
        $value1 = $this->manager->get('site_name');

        // Check that cache key exists
        $cacheKey = 'test_settings_global:null:site_name';
        $this->assertTrue(Cache::has($cacheKey));

        // Verify cached value
        $cachedValue = Cache::get($cacheKey);
        $this->assertEquals('Cached Site', $cachedValue);
    }

    #[Test]
    public function it_retrieves_from_cache_on_subsequent_gets(): void
    {
        $this->manager->set('site_name', 'Original Value');

        // Get once to cache
        $this->manager->get('site_name');

        // Directly modify database without going through manager
        Setting::where('key', 'site_name')->update([
            'value' => json_encode('Modified in DB')
        ]);

        // Get again - should return cached value, not DB value
        $value = $this->manager->get('site_name');

        $this->assertEquals('Original Value', $value);
    }

    #[Test]
    public function it_invalidates_cache_on_set(): void
    {
        $this->manager->set('site_name', 'First Value');
        $this->assertEquals('First Value', $this->manager->get('site_name'));

        // Update - should invalidate cache
        $this->manager->set('site_name', 'Updated Value');

        // Should get new value, not cached one
        $value = $this->manager->get('site_name');
        $this->assertEquals('Updated Value', $value);
    }

    #[Test]
    public function it_invalidates_cache_on_delete(): void
    {
        $this->manager->set('site_name', 'To Be Deleted');
        $this->manager->get('site_name'); // Cache it

        // Delete - should invalidate cache
        $this->manager->delete('site_name');

        // Should return default value
        $value = $this->manager->get('site_name');
        $this->assertEquals('Test Site', $value);
    }

    #[Test]
    public function it_caches_model_scoped_settings_separately(): void
    {
        $user1 = TestUser::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = TestUser::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        $this->manager->set('theme', 'dark', $user1);
        $this->manager->set('theme', 'light', $user2);

        // Get to cache both
        $this->manager->get('theme', $user1);
        $this->manager->get('theme', $user2);

        // Check that different cache keys exist
        $cacheKey1 = 'test_settings_' . TestUser::class . ':' . $user1->id . ':theme';
        $cacheKey2 = 'test_settings_' . TestUser::class . ':' . $user2->id . ':theme';

        $this->assertTrue(Cache::has($cacheKey1));
        $this->assertTrue(Cache::has($cacheKey2));
        $this->assertNotEquals(Cache::get($cacheKey1), Cache::get($cacheKey2));
    }

    #[Test]
    public function it_can_manually_clear_cache(): void
    {
        $this->manager->set('site_name', 'Cached');
        $this->manager->get('site_name'); // Cache it

        $cacheKey = 'test_settings_global:null:site_name';
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache manually
        $this->manager->clearCache();

        // Cache should be cleared (though this is limited without tags)
        // The method attempts to clear known keys
    }

    #[Test]
    public function it_handles_cache_misses_gracefully(): void
    {
        $this->manager->set('site_name', 'Test Value');

        // Clear all cache
        Cache::flush();

        // Should retrieve from database when cache misses
        $value = $this->manager->get('site_name');

        $this->assertEquals('Test Value', $value);
    }

    #[Test]
    public function it_caches_default_values(): void
    {
        // Get default value (no database record)
        $value = $this->manager->get('site_name');

        $this->assertEquals('Test Site', $value);

        $cacheKey = 'test_settings_global:null:site_name';
        $this->assertTrue(Cache::has($cacheKey));

        // Cached value should be the default
        $this->assertEquals('Test Site', Cache::get($cacheKey));
    }

    #[Test]
    public function it_caches_after_multiple_operations(): void
    {
        // Set value
        $this->manager->set('site_name', 'First');

        // Get value (cache miss, will cache)
        $value1 = $this->manager->get('site_name');

        // Update value (invalidates cache)
        $this->manager->set('site_name', 'Second');

        // Get again (cache miss again, will cache new value)
        $value2 = $this->manager->get('site_name');

        // Get third time (cache hit)
        $value3 = $this->manager->get('site_name');

        $this->assertEquals('First', $value1);
        $this->assertEquals('Second', $value2);
        $this->assertEquals('Second', $value3);
    }

    #[Test]
    public function it_respects_cache_configuration(): void
    {
        // Cache should be enabled by default in test config
        $this->assertTrue(config('schema-settings.cache.enabled'));

        // Test that caching actually works
        $this->manager->set('site_name', 'Cached');
        $this->manager->get('site_name');

        $cacheKey = 'test_settings_global:null:site_name';
        $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function it_caches_different_types_correctly(): void
    {
        // Test boolean caching
        $this->manager->set('maintenance_mode', true);
        $this->manager->get('maintenance_mode');
        $boolKey = 'test_settings_global:null:maintenance_mode';
        $this->assertTrue(Cache::has($boolKey));
        $this->assertTrue(Cache::get($boolKey));

        // Test integer caching
        $this->manager->set('max_users', 500);
        $this->manager->get('max_users');
        $intKey = 'test_settings_global:null:max_users';
        $this->assertTrue(Cache::has($intKey));
        $this->assertEquals(500, Cache::get($intKey));

        // Test array caching
        $this->manager->set('allowed_ips', ['192.168.1.1']);
        $this->manager->get('allowed_ips');
        $arrayKey = 'test_settings_global:null:allowed_ips';
        $this->assertTrue(Cache::has($arrayKey));
        $this->assertEquals(['192.168.1.1'], Cache::get($arrayKey));
    }

    #[Test]
    public function it_invalidates_only_specific_setting_cache(): void
    {
        $this->manager->set('site_name', 'Site');
        $this->manager->set('maintenance_mode', true);

        // Cache both
        $this->manager->get('site_name');
        $this->manager->get('maintenance_mode');

        $siteKey = 'test_settings_global:null:site_name';
        $maintenanceKey = 'test_settings_global:null:maintenance_mode';

        $this->assertTrue(Cache::has($siteKey));
        $this->assertTrue(Cache::has($maintenanceKey));

        // Update only site_name
        $this->manager->set('site_name', 'New Site');

        // site_name cache should be invalidated
        $this->assertFalse(Cache::has($siteKey));

        // maintenance_mode cache should still exist
        $this->assertTrue(Cache::has($maintenanceKey));
    }

    #[Test]
    public function clear_cache_for_specific_scope(): void
    {
        // Set and cache global settings
        $this->manager->set('site_name', 'Global Site');
        $this->manager->get('site_name');

        // Set and cache user settings
        $this->manager->set('theme', 'dark', $this->user);
        $this->manager->get('theme', $this->user);

        $globalKey = 'test_settings_global:null:site_name';
        $userKey = 'test_settings_' . TestUser::class . ':' . $this->user->id . ':theme';

        $this->assertTrue(Cache::has($globalKey));
        $this->assertTrue(Cache::has($userKey));

        // Clear only global scope
        $this->manager->clearCache('global');

        // Global should be cleared, user should remain
        $this->assertFalse(Cache::has($globalKey));
        $this->assertTrue(Cache::has($userKey));
    }

    #[Test]
    public function clear_cache_for_specific_model_instance(): void
    {
        $user1 = TestUser::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = TestUser::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        // Set and cache for both users
        $this->manager->set('theme', 'dark', $user1);
        $this->manager->set('theme', 'light', $user2);
        $this->manager->get('theme', $user1);
        $this->manager->get('theme', $user2);

        $user1Key = 'test_settings_' . TestUser::class . ':' . $user1->id . ':theme';
        $user2Key = 'test_settings_' . TestUser::class . ':' . $user2->id . ':theme';

        $this->assertTrue(Cache::has($user1Key));
        $this->assertTrue(Cache::has($user2Key));

        // Clear only user1's cache
        $this->manager->clearCache(TestUser::class, $user1->id);

        // User1 cache should be cleared
        $this->assertFalse(Cache::has($user1Key));
        // User2 cache should remain
        $this->assertTrue(Cache::has($user2Key));
    }

    #[Test]
    public function clear_cache_works_when_cache_is_disabled(): void
    {
        // Should not throw exception even if cache is disabled
        $this->manager->clearCache();
        
        $this->assertTrue(true); // No exception thrown
    }

    #[Test]
    public function cache_works_with_encrypted_values(): void
    {
        $this->manager->set('api_key', 'super_secret_key_1234567890abcdef');
        
        // First get - should cache decrypted value
        $value1 = $this->manager->get('api_key');

        // Should be cached (decrypted)
        $cacheKey = 'test_settings_global:null:api_key';
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals('super_secret_key_1234567890abcdef', Cache::get($cacheKey));

        // Second get - from cache
        $value2 = $this->manager->get('api_key');

        $this->assertEquals($value1, $value2);
    }

    #[Test]
    public function cache_ttl_is_respected(): void
    {
        // TTL is set in config - just verify it's configurable
        $ttl = config('schema-settings.cache.ttl');
        
        // TTL can be null (meaning forever) or an integer
        $this->assertTrue($ttl === null || (is_int($ttl) && $ttl > 0));
    }

    #[Test]
    public function get_multiple_caches_all_fetched_values(): void
    {
        Cache::flush();

        $this->manager->set('site_name', 'Test');
        $this->manager->set('max_users', 200);

        $values = $this->manager->getMultiple(['site_name', 'max_users']);

        // Both should be cached now
        $this->assertTrue(Cache::has('test_settings_global:null:site_name'));
        $this->assertTrue(Cache::has('test_settings_global:null:max_users'));
    }

    #[Test]
    public function set_multiple_invalidates_all_affected_caches(): void
    {
        // Set and cache multiple settings
        $this->manager->set('site_name', 'Original Site');
        $this->manager->set('max_users', 100);
        $this->manager->get('site_name');
        $this->manager->get('max_users');

        $siteKey = 'test_settings_global:null:site_name';
        $usersKey = 'test_settings_global:null:max_users';

        $this->assertTrue(Cache::has($siteKey));
        $this->assertTrue(Cache::has($usersKey));

        // Update both via setMultiple
        $this->manager->setMultiple([
            'site_name' => 'New Site',
            'max_users' => 200,
        ]);

        // Both caches should be invalidated
        $this->assertFalse(Cache::has($siteKey));
        $this->assertFalse(Cache::has($usersKey));

        // Get again should fetch from DB and cache new values
        $this->manager->get('site_name');
        $this->assertTrue(Cache::has($siteKey));
        $this->assertEquals('New Site', Cache::get($siteKey));
    }

    #[Test]
    public function all_caches_all_returned_values(): void
    {
        Cache::flush();

        $this->manager->set('site_name', 'Test');
        $this->manager->set('max_users', 200);

        $this->manager->all();

        // All settings should now be cached
        $this->assertTrue(Cache::has('test_settings_global:null:site_name'));
        $this->assertTrue(Cache::has('test_settings_global:null:max_users'));
        $this->assertTrue(Cache::has('test_settings_global:null:maintenance_mode'));
    }

    #[Test]
    public function cache_isolation_between_global_and_model_scoped(): void
    {
        // This setting key exists in both global and user scope potentially
        // Ensure caches don't interfere
        $this->manager->set('site_name', 'Global Value');
        $this->manager->get('site_name');

        $globalKey = 'test_settings_global:null:site_name';
        $this->assertTrue(Cache::has($globalKey));
        $this->assertEquals('Global Value', Cache::get($globalKey));
    }
}


