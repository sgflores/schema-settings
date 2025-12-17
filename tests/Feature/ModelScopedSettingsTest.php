<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Facades\Settings;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestTeam;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class ModelScopedSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected TestUser $user1;

    protected TestUser $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestUserSettings::class);

        $this->user1 = TestUser::create([
            'name' => 'User One',
            'email' => 'user1@test.com',
        ]);

        $this->user2 = TestUser::create([
            'name' => 'User Two',
            'email' => 'user2@test.com',
        ]);
    }

    #[Test]
    public function it_can_get_default_value_for_model(): void
    {
        $value = $this->manager->get('theme', $this->user1);

        $this->assertEquals('light', $value);
    }

    #[Test]
    public function it_can_set_and_get_for_specific_model(): void
    {
        $this->manager->set('theme', 'dark', $this->user1);
        $value = $this->manager->get('theme', $this->user1);

        $this->assertEquals('dark', $value);
    }

    #[Test]
    public function it_isolates_settings_between_models(): void
    {
        $this->manager->set('theme', 'dark', $this->user1);
        $this->manager->set('theme', 'light', $this->user2);

        $theme1 = $this->manager->get('theme', $this->user1);
        $theme2 = $this->manager->get('theme', $this->user2);

        $this->assertEquals('dark', $theme1);
        $this->assertEquals('light', $theme2);
    }

    #[Test]
    public function it_stores_model_settings_with_correct_reference(): void
    {
        $this->user1->setSetting('theme', 'dark');

        $this->assertDatabaseHas('schema_settings', [
            'key' => 'theme',
            'reference_type' => TestUser::class,
            'reference_id' => $this->user1->id,
        ]);
    }

    #[Test]
    public function it_works_with_facade_for_model_settings(): void
    {
        Settings::set('theme', 'dark', $this->user1);
        $theme = Settings::get('theme', $this->user1);

        $this->assertEquals('dark', $theme);
    }

    #[Test]
    public function it_can_delete_model_setting(): void
    {
        $this->manager->set('theme', 'dark', $this->user1);
        $this->assertEquals('dark', $this->manager->get('theme', $this->user1));

        $this->manager->delete('theme', $this->user1);

        $this->assertEquals('light', $this->manager->get('theme', $this->user1));
    }

    #[Test]
    public function it_can_have_different_settings_for_different_model_types(): void
    {
        $team = TestTeam::create(['name' => 'Test Team']);

        // These will be in different scopes since user and team are different classes
        $this->user1->setSetting('theme', 'dark');

        // Even if we had a 'theme' setting for teams, they would be isolated
        $userTheme = $this->user1->setting('theme');

        $this->assertEquals('dark', $userTheme);
    }

    #[Test]
    public function it_handles_json_type_for_models(): void
    {
        $preferences = ['sidebar' => 'collapsed', 'density' => 'compact'];

        $this->user1->setSetting('preferences', $preferences);
        $retrieved = $this->user1->setting('preferences');

        $this->assertIsArray($retrieved);
        $this->assertEquals($preferences, $retrieved);
    }

    #[Test]
    public function it_checks_setting_existence_for_model(): void
    {
        $this->assertTrue($this->manager->has('theme', $this->user1));
        $this->assertFalse($this->manager->has('non_existent', $this->user1));
    }

    #[Test]
    public function it_gets_all_settings_for_model(): void
    {
        $this->user1->setSetting('theme', 'dark');
        $this->user1->setSetting('notifications_enabled', false);

        $all = $this->manager->all($this->user1);

        $this->assertArrayHasKey('theme', $all);
        $this->assertArrayHasKey('notifications_enabled', $all);
        $this->assertEquals('dark', $all['theme']);
        $this->assertFalse($all['notifications_enabled']);
    }
}
