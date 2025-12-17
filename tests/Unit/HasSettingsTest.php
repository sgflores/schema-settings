<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class HasSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestUserSettings::class);

        $this->user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
        ]);
    }

    #[Test]
    public function trait_setting_method_can_get_setting_value(): void
    {
        $this->manager->set('theme', 'dark', $this->user);

        $value = $this->user->setting('theme');

        $this->assertEquals('dark', $value);
    }

    #[Test]
    public function trait_setting_method_returns_default_when_not_set(): void
    {
        $value = $this->user->setting('theme');

        $this->assertEquals('light', $value);
    }

    #[Test]
    public function trait_setting_method_throws_exception_for_invalid_key(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->user->setting('non_existent_key');
    }

    #[Test]
    public function trait_set_setting_method_can_set_value(): void
    {
        $result = $this->user->setSetting('theme', 'dark');

        $this->assertTrue($result);
        $this->assertEquals('dark', $this->manager->get('theme', $this->user));
    }

    #[Test]
    public function trait_set_setting_method_validates_value(): void
    {
        $this->expectException(ValidationException::class);

        $this->user->setSetting('theme', 'invalid_theme');
    }

    #[Test]
    public function trait_set_setting_method_throws_exception_for_invalid_key(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->user->setSetting('non_existent_key', 'value');
    }

    #[Test]
    public function trait_delete_setting_method_can_delete_setting(): void
    {
        $this->user->setSetting('theme', 'dark');
        $this->assertEquals('dark', $this->user->setting('theme'));

        $result = $this->user->deleteSetting('theme');

        $this->assertTrue($result);
        $this->assertEquals('light', $this->user->setting('theme')); // Returns to default
    }

    #[Test]
    public function trait_delete_setting_returns_false_when_not_exists(): void
    {
        $result = $this->user->deleteSetting('theme');

        $this->assertFalse($result);
    }

    #[Test]
    public function trait_delete_setting_throws_exception_for_invalid_key(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->user->deleteSetting('non_existent_key');
    }

    #[Test]
    public function trait_settings_method_can_get_multiple_settings(): void
    {
        $this->user->setSetting('theme', 'dark');
        $this->user->setSetting('notifications_enabled', false);

        $values = $this->user->settings(['theme', 'notifications_enabled']);

        $this->assertIsArray($values);
        $this->assertCount(2, $values);
        $this->assertEquals('dark', $values['theme']);
        $this->assertFalse($values['notifications_enabled']);
    }

    #[Test]
    public function trait_settings_method_returns_defaults_for_unset_values(): void
    {
        $values = $this->user->settings(['theme', 'notifications_enabled']);

        $this->assertEquals('light', $values['theme']);
        $this->assertTrue($values['notifications_enabled']);
    }

    #[Test]
    public function trait_settings_method_throws_exception_for_invalid_key(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->user->settings(['theme', 'non_existent_key']);
    }

    #[Test]
    public function trait_settings_method_works_with_empty_array(): void
    {
        $values = $this->user->settings([]);

        $this->assertIsArray($values);
        $this->assertEmpty($values);
    }

    #[Test]
    public function trait_all_settings_method_returns_all_settings(): void
    {
        $this->user->setSetting('theme', 'dark');

        $all = $this->user->allSettings();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('theme', $all);
        $this->assertArrayHasKey('notifications_enabled', $all);
        $this->assertArrayHasKey('items_per_page', $all);
    }

    #[Test]
    public function trait_all_settings_includes_defaults_for_unset_values(): void
    {
        $all = $this->user->allSettings();

        $this->assertEquals('light', $all['theme']);
        $this->assertTrue($all['notifications_enabled']);
    }

    #[Test]
    public function trait_all_settings_includes_custom_set_values(): void
    {
        $this->user->setSetting('theme', 'dark');
        $this->user->setSetting('notifications_enabled', false);

        $all = $this->user->allSettings();

        $this->assertEquals('dark', $all['theme']);
        $this->assertFalse($all['notifications_enabled']);
    }

    #[Test]
    public function trait_set_settings_method_can_set_multiple_values(): void
    {
        $result = $this->user->setSettings([
            'theme' => 'dark',
            'notifications_enabled' => false,
        ]);

        $this->assertTrue($result);
        $this->assertEquals('dark', $this->user->setting('theme'));
        $this->assertFalse($this->user->setting('notifications_enabled'));
    }

    #[Test]
    public function trait_set_settings_method_is_atomic(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->user->setSettings([
                'theme' => 'dark',
                'items_per_page' => 999, // Invalid - not in options
            ]);
        } catch (ValidationException $e) {
            // Verify first setting was NOT saved due to transaction rollback
            $this->assertEquals('light', $this->user->setting('theme'));
            throw $e;
        }
    }

    #[Test]
    public function trait_set_settings_throws_exception_for_invalid_key(): void
    {
        $this->expectException(SettingNotFoundException::class);

        $this->user->setSettings([
            'theme' => 'dark',
            'non_existent_key' => 'value',
        ]);
    }

    #[Test]
    public function trait_set_settings_validates_all_values(): void
    {
        $this->expectException(ValidationException::class);

        $this->user->setSettings([
            'theme' => 'dark',
            'items_per_page' => 'not_a_number', // Invalid type
        ]);
    }

    #[Test]
    public function trait_methods_work_with_different_model_instances(): void
    {
        $user2 = TestUser::create([
            'name' => 'User Two',
            'email' => 'user2@test.com',
        ]);

        $this->user->setSetting('theme', 'dark');
        $user2->setSetting('theme', 'light');

        $this->assertEquals('dark', $this->user->setting('theme'));
        $this->assertEquals('light', $user2->setting('theme'));
    }

    #[Test]
    public function trait_methods_are_scoped_to_model_instance(): void
    {
        $user2 = TestUser::create([
            'name' => 'User Two',
            'email' => 'user2@test.com',
        ]);

        $this->user->setSetting('theme', 'dark');

        // user2 should still have default
        $this->assertEquals('light', $user2->setting('theme'));
    }

    #[Test]
    public function trait_all_settings_is_scoped_to_model(): void
    {
        $user2 = TestUser::create([
            'name' => 'User Two',
            'email' => 'user2@test.com',
        ]);

        $this->user->setSetting('theme', 'dark');
        $user2->setSetting('theme', 'auto');

        $user1Settings = $this->user->allSettings();
        $user2Settings = $user2->allSettings();

        $this->assertEquals('dark', $user1Settings['theme']);
        $this->assertEquals('auto', $user2Settings['theme']);
    }

    #[Test]
    public function trait_methods_work_with_all_data_types(): void
    {
        $this->user->setSetting('theme', 'dark'); // string
        $this->user->setSetting('notifications_enabled', false); // boolean
        $this->user->setSetting('items_per_page', 50); // integer

        $this->assertEquals('dark', $this->user->setting('theme'));
        $this->assertFalse($this->user->setting('notifications_enabled'));
        $this->assertEquals(50, $this->user->setting('items_per_page'));
    }

    #[Test]
    public function trait_setting_method_returns_properly_cast_values(): void
    {
        $this->user->setSetting('notifications_enabled', false);

        $value = $this->user->setting('notifications_enabled');

        $this->assertIsBool($value);
        $this->assertFalse($value);
    }

    #[Test]
    public function trait_methods_work_after_model_is_refreshed(): void
    {
        $this->user->setSetting('theme', 'dark');

        $this->user->refresh();

        $this->assertEquals('dark', $this->user->setting('theme'));
    }
}
