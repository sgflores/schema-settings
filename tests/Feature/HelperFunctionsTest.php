<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Tests\TestCase;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;

class HelperFunctionsTest extends TestCase
{
    use RefreshDatabase;

    protected TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = app('schema-settings');
        $manager->register(TestGlobalSettings::class);
        $manager->register(TestUserSettings::class);

        $this->user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
        ]);
    }

    #[Test]
    public function setting_helper_gets_global_setting(): void
    {
        set_setting('site_name', 'Helper Test');
        
        $value = setting('site_name');

        $this->assertEquals('Helper Test', $value);
    }

    #[Test]
    public function setting_helper_returns_default_on_error(): void
    {
        $value = setting('non_existent', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    #[Test]
    public function setting_helper_works_with_model(): void
    {
        set_setting('theme', 'dark', $this->user);
        
        $value = setting('theme', null, $this->user);

        $this->assertEquals('dark', $value);
    }

    #[Test]
    public function set_setting_helper_sets_global_setting(): void
    {
        $result = set_setting('site_name', 'Helper Set');

        $this->assertTrue($result);
        $this->assertEquals('Helper Set', setting('site_name'));
    }

    #[Test]
    public function set_setting_helper_returns_false_on_error(): void
    {
        $result = set_setting('non_existent', 'value');

        $this->assertFalse($result);
    }

    #[Test]
    public function set_setting_helper_works_with_model(): void
    {
        $result = set_setting('theme', 'auto', $this->user);

        $this->assertTrue($result);
        $this->assertEquals('auto', $this->user->setting('theme'));
    }

    #[Test]
    public function has_setting_helper_checks_existence(): void
    {
        $this->assertTrue(has_setting('site_name'));
        $this->assertFalse(has_setting('non_existent'));
    }

    #[Test]
    public function has_setting_helper_works_with_model(): void
    {
        $this->assertTrue(has_setting('theme', $this->user));
        $this->assertFalse(has_setting('non_existent', $this->user));
    }

    #[Test]
    public function delete_setting_helper_deletes_setting(): void
    {
        set_setting('site_name', 'To Delete');
        
        $result = delete_setting('site_name');

        $this->assertTrue($result);
        $this->assertEquals('Test Site', setting('site_name')); // Returns default
    }

    #[Test]
    public function delete_setting_helper_returns_false_on_error(): void
    {
        $result = delete_setting('non_existent');

        $this->assertFalse($result);
    }

    #[Test]
    public function delete_setting_helper_works_with_model(): void
    {
        set_setting('theme', 'dark', $this->user);
        
        $result = delete_setting('theme', $this->user);

        $this->assertTrue($result);
        $this->assertEquals('light', setting('theme', null, $this->user)); // Returns default
    }

    #[Test]
    public function settings_helper_gets_multiple_settings(): void
    {
        set_setting('site_name', 'Site');
        set_setting('maintenance_mode', true);

        $values = settings(['site_name', 'maintenance_mode']);

        $this->assertEquals('Site', $values['site_name']);
        $this->assertTrue($values['maintenance_mode']);
    }

    #[Test]
    public function settings_helper_returns_empty_array_on_error(): void
    {
        $values = settings(['non_existent']);

        $this->assertEquals([], $values);
    }

    #[Test]
    public function settings_helper_works_with_model(): void
    {
        set_setting('theme', 'dark', $this->user);
        set_setting('notifications_enabled', false, $this->user);

        $values = settings(['theme', 'notifications_enabled'], $this->user);

        $this->assertEquals('dark', $values['theme']);
        $this->assertFalse($values['notifications_enabled']);
    }

    #[Test]
    public function all_settings_helper_gets_all_global_settings(): void
    {
        set_setting('site_name', 'All Test');

        $all = all_settings();

        $this->assertArrayHasKey('site_name', $all);
        $this->assertArrayHasKey('maintenance_mode', $all);
        $this->assertEquals('All Test', $all['site_name']);
    }

    #[Test]
    public function all_settings_helper_returns_empty_array_on_error(): void
    {
        // This shouldn't error, but testing graceful handling
        $all = all_settings();

        $this->assertIsArray($all);
    }

    #[Test]
    public function all_settings_helper_works_with_model(): void
    {
        set_setting('theme', 'dark', $this->user);

        $all = all_settings($this->user);

        $this->assertArrayHasKey('theme', $all);
        $this->assertEquals('dark', $all['theme']);
    }

    #[Test]
    public function helpers_handle_validation_errors_gracefully(): void
    {
        // site_name has min:3 rule
        $result = set_setting('site_name', 'AB');

        $this->assertFalse($result); // Should return false, not throw exception
    }

    #[Test]
    public function helpers_handle_readonly_errors_gracefully(): void
    {
        $result = set_setting('installation_date', 'new value');

        $this->assertFalse($result); // Should return false, not throw exception
    }
}

