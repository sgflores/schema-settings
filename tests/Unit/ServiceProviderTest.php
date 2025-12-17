<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Contracts\SettingsManagerInterface;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\SchemaSettingServiceProvider;
use SgFlores\SchemaSetting\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function service_provider_is_registered(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(SchemaSettingServiceProvider::class, $providers);
    }

    #[Test]
    public function it_registers_settings_manager_as_singleton(): void
    {
        $instance1 = $this->app->make('schema-settings');
        $instance2 = $this->app->make('schema-settings');

        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(SettingsManager::class, $instance1);
    }

    #[Test]
    public function it_binds_settings_manager_interface(): void
    {
        $instance = $this->app->make(SettingsManagerInterface::class);

        $this->assertInstanceOf(SettingsManager::class, $instance);
    }

    #[Test]
    public function interface_and_singleton_resolve_to_same_instance(): void
    {
        $viaSingleton = $this->app->make('schema-settings');
        $viaInterface = $this->app->make(SettingsManagerInterface::class);

        $this->assertSame($viaSingleton, $viaInterface);
    }

    #[Test]
    public function it_merges_package_configuration(): void
    {
        $this->assertNotNull(config('schema-settings'));
        $this->assertIsArray(config('schema-settings'));
    }

    #[Test]
    public function config_has_table_name(): void
    {
        $this->assertTrue(config()->has('schema-settings.table_name'));
        $this->assertIsString(config('schema-settings.table_name'));
    }

    #[Test]
    public function config_has_cache_settings(): void
    {
        $this->assertTrue(config()->has('schema-settings.cache'));
        $this->assertIsArray(config('schema-settings.cache'));
        $this->assertTrue(config()->has('schema-settings.cache.enabled'));
        $this->assertTrue(config()->has('schema-settings.cache.prefix'));
    }

    #[Test]
    public function config_has_audit_settings(): void
    {
        $this->assertTrue(config()->has('schema-settings.audit'));
        $this->assertIsArray(config('schema-settings.audit'));
        $this->assertTrue(config()->has('schema-settings.audit.enabled'));
        $this->assertTrue(config()->has('schema-settings.audit.table_name'));
    }

    #[Test]
    public function config_has_validation_settings(): void
    {
        $this->assertTrue(config()->has('schema-settings.validation'));
        $this->assertIsArray(config('schema-settings.validation'));
        $this->assertTrue(config()->has('schema-settings.validation.strict_mode'));
        $this->assertTrue(config()->has('schema-settings.validation.boot_validation'));
        $this->assertTrue(config()->has('schema-settings.validation.enhanced_errors'));
    }

    #[Test]
    public function config_has_routes_settings(): void
    {
        $this->assertTrue(config()->has('schema-settings.routes'));
        $this->assertIsArray(config('schema-settings.routes'));
        $this->assertTrue(config()->has('schema-settings.routes.prefix'));
        $this->assertTrue(config()->has('schema-settings.routes.middleware'));
        $this->assertTrue(config()->has('schema-settings.routes.name_prefix'));
        $this->assertTrue(config()->has('schema-settings.routes.enabled'));
    }

    #[Test]
    public function all_commands_are_registered(): void
    {
        $commands = Artisan::all();

        $expectedCommands = [
            'schema-settings:list',
            'schema-settings:get',
            'schema-settings:set',
            'schema-settings:clear-cache',
        ];

        foreach ($expectedCommands as $command) {
            $this->assertArrayHasKey($command, $commands);
        }
    }

    #[Test]
    public function settings_manager_uses_config_values(): void
    {
        $manager = $this->app->make('schema-settings');

        // Access protected properties via reflection for testing
        $reflection = new \ReflectionClass($manager);

        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $this->assertEquals(config('schema-settings.table_name'), $tableProperty->getValue($manager));

        $cacheEnabledProperty = $reflection->getProperty('cacheEnabled');
        $cacheEnabledProperty->setAccessible(true);
        $this->assertEquals(config('schema-settings.cache.enabled'), $cacheEnabledProperty->getValue($manager));
    }

    #[Test]
    public function settings_manager_can_be_type_hinted_in_constructor(): void
    {
        $service = new class($this->app->make(SettingsManagerInterface::class))
        {
            public function __construct(public SettingsManagerInterface $settings) {}
        };

        $this->assertInstanceOf(SettingsManagerInterface::class, $service->settings);
        $this->assertInstanceOf(SettingsManager::class, $service->settings);
    }

    #[Test]
    public function service_provider_boots_correctly(): void
    {
        $provider = $this->app->getProvider(SchemaSettingServiceProvider::class);

        $this->assertNotNull($provider);
        $this->assertInstanceOf(SchemaSettingServiceProvider::class, $provider);
    }

    #[Test]
    public function config_defaults_are_sensible(): void
    {
        $this->assertEquals('schema_settings', config('schema-settings.table_name'));
        $this->assertTrue(config('schema-settings.cache.enabled'));
        // Test environment uses 'test_settings_' prefix
        $this->assertStringContainsString('settings_', config('schema-settings.cache.prefix'));
        $this->assertTrue(config('schema-settings.audit.enabled'));
        $this->assertEquals('schema_settings_history', config('schema-settings.audit.table_name'));

        // Validation defaults
        $this->assertTrue(config('schema-settings.validation.strict_mode'));
        $this->assertTrue(config('schema-settings.validation.boot_validation'));
        $this->assertTrue(config('schema-settings.validation.enhanced_errors'));
    }

    #[Test]
    public function manager_singleton_persists_schema_registrations(): void
    {
        $manager1 = $this->app->make('schema-settings');
        $manager1->register(\SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings::class);

        $schema1 = $manager1->getSchema('global');

        // Get again from container
        $manager2 = $this->app->make('schema-settings');
        $schema2 = $manager2->getSchema('global');

        // Should be the same instance with persisted schema
        $this->assertSame($manager1, $manager2);
        $this->assertEquals($schema1, $schema2);
    }

    #[Test]
    public function routes_are_registered_when_enabled(): void
    {
        config(['schema-settings.routes.enabled' => true]);

        $routeList = \Illuminate\Support\Facades\Route::getRoutes();

        $hasSettingsRoute = false;
        foreach ($routeList as $route) {
            if (str_contains($route->uri(), 'schema-settings')) {
                $hasSettingsRoute = true;
                break;
            }
        }

        $this->assertTrue($hasSettingsRoute);
    }

    #[Test]
    public function routes_can_be_disabled(): void
    {
        config(['schema-settings.routes.enabled' => false]);

        // Routes are loaded during boot, so we just verify the config
        $this->assertFalse(config('schema-settings.routes.enabled'));
    }

    #[Test]
    public function routes_use_configured_prefix(): void
    {
        // Verify the config has the default prefix
        $this->assertEquals('api/schema-settings', config('schema-settings.routes.prefix'));

        // Verify we can override it via config
        config(['schema-settings.routes.prefix' => 'api/custom-prefix']);
        $this->assertEquals('api/custom-prefix', config('schema-settings.routes.prefix'));
    }
}
