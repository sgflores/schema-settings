<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Models\SettingHistory;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\TestCase;

class SettingHistoryModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Don't call createTestTables() - already done in parent
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $history = new SettingHistory;

        $this->assertInstanceOf(SettingHistory::class, $history);
    }

    #[Test]
    public function it_uses_correct_table_name_from_config(): void
    {
        $history = new SettingHistory;

        $this->assertEquals('schema_settings_history', $history->getTable());
    }

    #[Test]
    public function it_uses_custom_table_name_from_config(): void
    {
        config(['schema-settings.audit.table_name' => 'custom_history']);

        $history = new SettingHistory;

        $this->assertEquals('custom_history', $history->getTable());
    }

    #[Test]
    public function it_has_correct_fillable_attributes(): void
    {
        $history = new SettingHistory;

        $expected = [
            'key',
            'old_value',
            'new_value',
            'reference_type',
            'reference_id',
            'user_type',
            'user_id',
            'action',
            'created_at',
        ];

        $this->assertEquals($expected, $history->getFillable());
    }

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $history = new SettingHistory;

        $this->assertFalse($history->timestamps);
    }

    #[Test]
    public function it_casts_created_at_to_datetime(): void
    {
        $history = new SettingHistory;

        $casts = $history->getCasts();

        $this->assertArrayHasKey('created_at', $casts);
        $this->assertEquals('datetime', $casts['created_at']);
    }

    #[Test]
    public function it_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'key' => 'test_key',
            'old_value' => 'old',
            'new_value' => 'new',
            'reference_type' => null,
            'reference_id' => null,
            'user_type' => null,
            'user_id' => null,
            'action' => 'updated',
            'created_at' => now(),
        ];

        $history = SettingHistory::create($data);

        $this->assertDatabaseHas('schema_settings_history', [
            'key' => 'test_key',
            'action' => 'updated',
        ]);
        $this->assertEquals('test_key', $history->key);
        $this->assertEquals('updated', $history->action);
    }

    #[Test]
    public function it_has_reference_polymorphic_relationship(): void
    {
        $history = new SettingHistory;

        $relation = $history->reference();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $relation);
    }

    #[Test]
    public function it_has_user_polymorphic_relationship(): void
    {
        $history = new SettingHistory;

        $relation = $history->user();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $relation);
    }

    #[Test]
    public function key_scope_filters_by_setting_key(): void
    {
        SettingHistory::create([
            'key' => 'setting1',
            'action' => 'created',
            'new_value' => 'value1',
            'created_at' => now(),
        ]);

        SettingHistory::create([
            'key' => 'setting2',
            'action' => 'created',
            'new_value' => 'value2',
            'created_at' => now(),
        ]);

        $results = SettingHistory::key('setting1')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('setting1', $results->first()->key);
    }

    #[Test]
    public function action_scope_filters_by_action_type(): void
    {
        SettingHistory::create([
            'key' => 'test1',
            'action' => 'created',
            'new_value' => 'value',
            'created_at' => now(),
        ]);

        SettingHistory::create([
            'key' => 'test2',
            'action' => 'updated',
            'old_value' => 'old',
            'new_value' => 'new',
            'created_at' => now(),
        ]);

        SettingHistory::create([
            'key' => 'test3',
            'action' => 'deleted',
            'old_value' => 'value',
            'created_at' => now(),
        ]);

        $created = SettingHistory::action('created')->get();
        $updated = SettingHistory::action('updated')->get();
        $deleted = SettingHistory::action('deleted')->get();

        $this->assertCount(1, $created);
        $this->assertCount(1, $updated);
        $this->assertCount(1, $deleted);
    }

    #[Test]
    public function scopes_can_be_chained(): void
    {
        SettingHistory::create([
            'key' => 'theme',
            'action' => 'created',
            'new_value' => 'light',
            'created_at' => now(),
        ]);

        SettingHistory::create([
            'key' => 'theme',
            'action' => 'updated',
            'old_value' => 'light',
            'new_value' => 'dark',
            'created_at' => now(),
        ]);

        SettingHistory::create([
            'key' => 'language',
            'action' => 'created',
            'new_value' => 'en',
            'created_at' => now(),
        ]);

        // Chain key and action scopes
        $result = SettingHistory::key('theme')->action('updated')->first();

        $this->assertNotNull($result);
        $this->assertEquals('theme', $result->key);
        $this->assertEquals('updated', $result->action);
        $this->assertEquals('dark', $result->new_value);
    }

    #[Test]
    public function it_can_track_created_action(): void
    {
        $history = SettingHistory::create([
            'key' => 'new_setting',
            'old_value' => null,
            'new_value' => 'initial_value',
            'action' => 'created',
            'created_at' => now(),
        ]);

        $this->assertEquals('created', $history->action);
        $this->assertNull($history->old_value);
        $this->assertEquals('initial_value', $history->new_value);
    }

    #[Test]
    public function it_can_track_updated_action(): void
    {
        $history = SettingHistory::create([
            'key' => 'existing_setting',
            'old_value' => 'old_value',
            'new_value' => 'new_value',
            'action' => 'updated',
            'created_at' => now(),
        ]);

        $this->assertEquals('updated', $history->action);
        $this->assertEquals('old_value', $history->old_value);
        $this->assertEquals('new_value', $history->new_value);
    }

    #[Test]
    public function it_can_track_deleted_action(): void
    {
        $history = SettingHistory::create([
            'key' => 'deleted_setting',
            'old_value' => 'last_value',
            'new_value' => null,
            'action' => 'deleted',
            'created_at' => now(),
        ]);

        $this->assertEquals('deleted', $history->action);
        $this->assertEquals('last_value', $history->old_value);
        $this->assertNull($history->new_value);
    }

    #[Test]
    public function it_can_store_user_information(): void
    {
        $user = TestUser::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $history = SettingHistory::create([
            'key' => 'user_changed',
            'action' => 'updated',
            'old_value' => 'old',
            'new_value' => 'new',
            'user_type' => TestUser::class,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->assertEquals(TestUser::class, $history->user_type);
        $this->assertEquals($user->id, $history->user_id);
    }

    #[Test]
    public function it_can_store_reference_information(): void
    {
        $user = TestUser::create(['name' => 'Referenced User', 'email' => 'ref@test.com']);

        $history = SettingHistory::create([
            'key' => 'user_setting',
            'action' => 'created',
            'new_value' => 'value',
            'reference_type' => TestUser::class,
            'reference_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->assertEquals(TestUser::class, $history->reference_type);
        $this->assertEquals($user->id, $history->reference_id);
    }

    #[Test]
    public function it_orders_by_created_at_chronologically(): void
    {
        $first = SettingHistory::create([
            'key' => 'test',
            'action' => 'created',
            'new_value' => '1',
            'created_at' => now()->subHours(2),
        ]);

        $second = SettingHistory::create([
            'key' => 'test',
            'action' => 'updated',
            'old_value' => '1',
            'new_value' => '2',
            'created_at' => now()->subHour(),
        ]);

        $third = SettingHistory::create([
            'key' => 'test',
            'action' => 'updated',
            'old_value' => '2',
            'new_value' => '3',
            'created_at' => now(),
        ]);

        $history = SettingHistory::key('test')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertEquals('created', $history[0]->action);
        $this->assertEquals('updated', $history[1]->action);
        $this->assertEquals('updated', $history[2]->action);
    }

    #[Test]
    public function it_can_store_null_values_for_optional_fields(): void
    {
        $history = SettingHistory::create([
            'key' => 'minimal',
            'action' => 'created',
            'new_value' => 'value',
            'old_value' => null,
            'reference_type' => null,
            'reference_id' => null,
            'user_type' => null,
            'user_id' => null,
            'created_at' => now(),
        ]);

        $this->assertNull($history->old_value);
        $this->assertNull($history->reference_type);
        $this->assertNull($history->reference_id);
        $this->assertNull($history->user_type);
        $this->assertNull($history->user_id);
    }

    #[Test]
    public function it_does_not_have_updated_at_timestamp(): void
    {
        $history = SettingHistory::create([
            'key' => 'test',
            'action' => 'created',
            'new_value' => 'value',
            'created_at' => now(),
        ]);

        // Should not have updated_at column since timestamps are disabled
        $this->assertObjectNotHasProperty('updated_at', $history);
    }
}
