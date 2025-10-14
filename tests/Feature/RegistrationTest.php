<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Tests\TestCase;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Items\ConfigurableItem;
use SgFlores\SchemaSetting\Exceptions\InvalidConfigurableException;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
    }

    #[Test]
    public function it_can_register_a_global_settings_class(): void
    {
        $this->manager->register(TestGlobalSettings::class);

        $schema = $this->manager->getSchema('global');

        $this->assertNotEmpty($schema);
        $this->assertIsArray($schema);
    }

    #[Test]
    public function it_can_register_a_model_scoped_settings_class(): void
    {
        $this->manager->register(TestUserSettings::class);

        $schema = $this->manager->getSchema(TestUser::class);

        $this->assertNotEmpty($schema);
        $this->assertIsArray($schema);
    }

    #[Test]
    public function it_throws_exception_when_registering_non_configurable_class(): void
    {
        $this->expectException(InvalidConfigurableException::class);
        $this->expectExceptionMessage('must implement SgFlores\SchemaSetting\Contracts\ConfigurableInterface');

        $this->manager->register(\stdClass::class);
    }

    #[Test]
    public function it_stores_all_schema_items_from_registration(): void
    {
        $this->manager->register(TestGlobalSettings::class);

        $schema = $this->manager->getSchema('global');

        // Check that all defined settings are registered
        $this->assertArrayHasKey('site_name', $schema);
        $this->assertArrayHasKey('maintenance_mode', $schema);
        $this->assertArrayHasKey('max_users', $schema);
        $this->assertArrayHasKey('tax_rate', $schema);
        $this->assertArrayHasKey('allowed_ips', $schema);
        $this->assertArrayHasKey('metadata', $schema);
        $this->assertArrayHasKey('installation_date', $schema);
        $this->assertArrayHasKey('api_key', $schema);
        $this->assertArrayHasKey('language', $schema);
        $this->assertArrayHasKey('features', $schema);
    }

    #[Test]
    public function it_stores_schema_item_details_correctly(): void
    {
        $this->manager->register(TestGlobalSettings::class);

        $schema = $this->manager->getSchema('global');
        $siteNameConfig = $schema['site_name'];

        // Check ConfigurableItem is stored
        $this->assertInstanceOf(ConfigurableItem::class, $siteNameConfig);
        $this->assertEquals('site_name', $siteNameConfig->key);
        $this->assertEquals(ConfigurableItem::TYPE_STRING, $siteNameConfig->type);
        $this->assertEquals('Test Site', $siteNameConfig->default);
    }

    #[Test]
    public function it_can_register_multiple_settings_classes(): void
    {
        $this->manager->register(TestGlobalSettings::class);
        $this->manager->register(TestUserSettings::class);

        $globalSchema = $this->manager->getSchema('global');
        $userSchema = $this->manager->getSchema(TestUser::class);

        $this->assertNotEmpty($globalSchema);
        $this->assertNotEmpty($userSchema);
        $this->assertNotEquals($globalSchema, $userSchema);
    }

    #[Test]
    public function it_returns_all_schemas_when_called_without_scope(): void
    {
        $this->manager->register(TestGlobalSettings::class);
        $this->manager->register(TestUserSettings::class);

        $allSchemas = $this->manager->getSchema();

        $this->assertArrayHasKey('global', $allSchemas);
        $this->assertArrayHasKey(TestUser::class, $allSchemas);
    }

    #[Test]
    public function it_returns_empty_array_for_unregistered_scope(): void
    {
        $schema = $this->manager->getSchema('non_existent_scope');

        $this->assertIsArray($schema);
        $this->assertEmpty($schema);
    }

    #[Test]
    public function it_preserves_all_configurable_item_properties(): void
    {
        $this->manager->register(TestUserSettings::class);

        $schema = $this->manager->getSchema(TestUser::class);
        $themeConfig = $schema['theme'];

        $this->assertEquals('theme', $themeConfig->key);
        $this->assertEquals(ConfigurableItem::TYPE_STRING, $themeConfig->type);
        $this->assertEquals('light', $themeConfig->default);
        $this->assertEquals('appearance', $themeConfig->group);
        $this->assertEquals('Theme Preference', $themeConfig->label);
        $this->assertEquals('Choose your preferred color theme', $themeConfig->description);
        $this->assertEquals(['light', 'dark', 'auto'], $themeConfig->options);
    }

    #[Test]
    public function it_preserves_validation_rules_on_registration(): void
    {
        $this->manager->register(TestGlobalSettings::class);

        $schema = $this->manager->getSchema('global');
        $siteNameConfig = $schema['site_name'];

        $this->assertContains('required', $siteNameConfig->rules);
        $this->assertContains('min:3', $siteNameConfig->rules);
        $this->assertContains('max:100', $siteNameConfig->rules);
    }

    #[Test]
    public function it_preserves_special_flags_on_registration(): void
    {
        $this->manager->register(TestGlobalSettings::class);

        $schema = $this->manager->getSchema('global');

        // Check readonly flag
        $installDateConfig = $schema['installation_date'];
        $this->assertTrue($installDateConfig->readonly);

        // Check encrypted flag
        $apiKeyConfig = $schema['api_key'];
        $this->assertTrue($apiKeyConfig->encrypted);
    }

    #[Test]
    public function it_can_check_if_setting_exists_after_registration(): void
    {
        $this->manager->register(TestGlobalSettings::class);

        $this->assertTrue($this->manager->has('site_name'));
        $this->assertTrue($this->manager->has('maintenance_mode'));
        $this->assertFalse($this->manager->has('non_existent_setting'));
    }

    #[Test]
    public function it_can_check_model_scoped_settings_exist(): void
    {
        $this->manager->register(TestUserSettings::class);

        $user = TestUser::create(['name' => 'Test', 'email' => 'test@test.com']);

        $this->assertTrue($this->manager->has('theme', $user));
        $this->assertFalse($this->manager->has('non_existent', $user));
    }
}

