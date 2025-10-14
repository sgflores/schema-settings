<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\TestCase;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use PHPUnit\Framework\Attributes\Test;

class SettingModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Don't call createTestTables() - already done in parent
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $setting = new Setting();
        
        $this->assertInstanceOf(Setting::class, $setting);
    }

    #[Test]
    public function it_uses_correct_table_name_from_config(): void
    {
        $setting = new Setting();
        
        $this->assertEquals('schema_settings', $setting->getTable());
    }

    #[Test]
    public function it_uses_custom_table_name_from_config(): void
    {
        config(['schema-settings.table_name' => 'custom_settings']);
        
        $setting = new Setting();
        
        $this->assertEquals('custom_settings', $setting->getTable());
    }

    #[Test]
    public function it_has_correct_fillable_attributes(): void
    {
        $setting = new Setting();
        
        $expected = [
            'key',
            'value',
            'reference_type',
            'reference_id',
        ];
        
        $this->assertEquals($expected, $setting->getFillable());
    }

    #[Test]
    public function it_casts_timestamps_correctly(): void
    {
        $setting = new Setting();
        
        $casts = $setting->getCasts();
        
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertEquals('datetime', $casts['created_at']);
        $this->assertEquals('datetime', $casts['updated_at']);
    }

    #[Test]
    public function it_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'key' => 'test_key',
            'value' => 'test_value',
            'reference_type' => null,
            'reference_id' => null,
        ];
        
        $setting = Setting::create($data);
        
        $this->assertDatabaseHas('schema_settings', $data);
        $this->assertEquals('test_key', $setting->key);
        $this->assertEquals('test_value', $setting->value);
    }

    #[Test]
    public function it_has_reference_polymorphic_relationship(): void
    {
        $setting = new Setting();
        
        $relation = $setting->reference();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $relation);
    }

    #[Test]
    public function global_scope_filters_global_settings(): void
    {
        // Create global setting
        Setting::create([
            'key' => 'global_setting',
            'value' => 'value1',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        // Create model-scoped setting
        Setting::create([
            'key' => 'user_setting',
            'value' => 'value2',
            'reference_type' => TestUser::class,
            'reference_id' => 1,
        ]);

        $globalSettings = Setting::global()->get();

        $this->assertCount(1, $globalSettings);
        $this->assertEquals('global_setting', $globalSettings->first()->key);
    }

    #[Test]
    public function for_model_scope_filters_by_model_instance(): void
    {
        $user1 = TestUser::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = TestUser::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        // Create settings for different users
        Setting::create([
            'key' => 'theme',
            'value' => 'dark',
            'reference_type' => TestUser::class,
            'reference_id' => $user1->id,
        ]);

        Setting::create([
            'key' => 'theme',
            'value' => 'light',
            'reference_type' => TestUser::class,
            'reference_id' => $user2->id,
        ]);

        $user1Settings = Setting::forModel($user1)->get();
        $user2Settings = Setting::forModel($user2)->get();

        $this->assertCount(1, $user1Settings);
        $this->assertCount(1, $user2Settings);
        $this->assertEquals('dark', $user1Settings->first()->value);
        $this->assertEquals('light', $user2Settings->first()->value);
    }

    #[Test]
    public function key_scope_filters_by_setting_key(): void
    {
        Setting::create([
            'key' => 'setting1',
            'value' => 'value1',
        ]);

        Setting::create([
            'key' => 'setting2',
            'value' => 'value2',
        ]);

        $results = Setting::key('setting1')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('setting1', $results->first()->key);
    }

    #[Test]
    public function scopes_can_be_chained(): void
    {
        $user = TestUser::create(['name' => 'Test User', 'email' => 'test@test.com']);

        Setting::create([
            'key' => 'theme',
            'value' => 'dark',
            'reference_type' => TestUser::class,
            'reference_id' => $user->id,
        ]);

        Setting::create([
            'key' => 'language',
            'value' => 'en',
            'reference_type' => TestUser::class,
            'reference_id' => $user->id,
        ]);

        Setting::create([
            'key' => 'theme',
            'value' => 'light',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        // Chain forModel and key scopes
        $result = Setting::forModel($user)->key('theme')->first();

        $this->assertNotNull($result);
        $this->assertEquals('dark', $result->value);
        $this->assertEquals($user->id, $result->reference_id);
    }

    #[Test]
    public function it_has_timestamps(): void
    {
        $setting = Setting::create([
            'key' => 'test',
            'value' => 'value',
        ]);

        $this->assertNotNull($setting->created_at);
        $this->assertNotNull($setting->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $setting->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $setting->updated_at);
    }

    #[Test]
    public function it_can_store_null_reference_type_and_id(): void
    {
        $setting = Setting::create([
            'key' => 'global_key',
            'value' => 'global_value',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $this->assertNull($setting->reference_type);
        $this->assertNull($setting->reference_id);
    }

    #[Test]
    public function it_can_store_model_reference(): void
    {
        $user = TestUser::create(['name' => 'Test', 'email' => 'test@test.com']);

        $setting = Setting::create([
            'key' => 'user_key',
            'value' => 'user_value',
            'reference_type' => TestUser::class,
            'reference_id' => $user->id,
        ]);

        $this->assertEquals(TestUser::class, $setting->reference_type);
        $this->assertEquals($user->id, $setting->reference_id);
    }

    #[Test]
    public function it_can_update_existing_setting(): void
    {
        $setting = Setting::create([
            'key' => 'updatable',
            'value' => 'old_value',
        ]);

        $setting->update(['value' => 'new_value']);

        $this->assertEquals('new_value', $setting->fresh()->value);
    }

    #[Test]
    public function it_can_be_deleted(): void
    {
        $setting = Setting::create([
            'key' => 'deletable',
            'value' => 'value',
        ]);

        $id = $setting->id;
        $setting->delete();

        $this->assertDatabaseMissing('schema_settings', ['id' => $id]);
    }
}

