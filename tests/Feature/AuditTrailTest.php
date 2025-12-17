<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\SettingHistory;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class AuditTrailTest extends TestCase
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
    public function it_records_audit_trail_on_create(): void
    {
        $this->manager->set('site_name', 'New Site');

        $this->assertDatabaseHas('schema_settings_history', [
            'key' => 'site_name',
            'action' => 'created',
            'reference_type' => null,
            'reference_id' => null,
        ]);
    }

    #[Test]
    public function it_records_audit_trail_on_update(): void
    {
        $this->manager->set('site_name', 'Old Site');
        $this->manager->set('site_name', 'New Site');

        $history = SettingHistory::where('key', 'site_name')
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('updated', $history->action);
    }

    #[Test]
    public function it_records_audit_trail_on_delete(): void
    {
        $this->manager->set('site_name', 'To Delete');
        $this->manager->delete('site_name');

        $this->assertDatabaseHas('schema_settings_history', [
            'key' => 'site_name',
            'action' => 'deleted',
        ]);
    }

    #[Test]
    public function it_records_old_and_new_values(): void
    {
        $this->manager->set('site_name', 'Old Value');
        $this->manager->set('site_name', 'New Value');

        $history = SettingHistory::where('key', 'site_name')
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals(json_encode('Old Value'), $history->old_value);
        $this->assertEquals(json_encode('New Value'), $history->new_value);
    }

    #[Test]
    public function it_records_null_old_value_on_create(): void
    {
        $this->manager->set('site_name', 'New Site');

        $history = SettingHistory::where('key', 'site_name')
            ->where('action', 'created')
            ->first();

        $this->assertNull($history->old_value);
        $this->assertEquals(json_encode('New Site'), $history->new_value);
    }

    #[Test]
    public function it_records_null_new_value_on_delete(): void
    {
        $this->manager->set('site_name', 'To Delete');
        $this->manager->delete('site_name');

        $history = SettingHistory::where('key', 'site_name')
            ->where('action', 'deleted')
            ->first();

        $this->assertEquals(json_encode('To Delete'), $history->old_value);
        $this->assertNull($history->new_value);
    }

    #[Test]
    public function it_records_model_scoped_audit_trail(): void
    {
        $this->user->setSetting('theme', 'dark');

        $this->assertDatabaseHas('schema_settings_history', [
            'key' => 'theme',
            'action' => 'created',
            'reference_type' => TestUser::class,
            'reference_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_records_multiple_changes_in_history(): void
    {
        $this->manager->set('site_name', 'First');
        $this->manager->set('site_name', 'Second');
        $this->manager->set('site_name', 'Third');

        $count = SettingHistory::where('key', 'site_name')->count();

        $this->assertEquals(3, $count); // 1 created + 2 updated
    }

    #[Test]
    public function it_maintains_history_chronologically(): void
    {
        $this->manager->set('site_name', 'First');
        $this->manager->set('site_name', 'Second');
        $this->manager->set('site_name', 'Third');

        $history = SettingHistory::where('key', 'site_name')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertEquals('created', $history[0]->action);
        $this->assertEquals('updated', $history[1]->action);
        $this->assertEquals('updated', $history[2]->action);
    }

    #[Test]
    public function it_can_query_history_by_key(): void
    {
        $this->manager->set('site_name', 'Site');
        $this->manager->set('maintenance_mode', true);

        $siteHistory = SettingHistory::key('site_name')->get();
        $maintenanceHistory = SettingHistory::key('maintenance_mode')->get();

        $this->assertCount(1, $siteHistory);
        $this->assertCount(1, $maintenanceHistory);
    }

    #[Test]
    public function it_can_query_history_by_action(): void
    {
        $this->manager->set('site_name', 'New');
        $this->manager->set('site_name', 'Updated');
        $this->manager->delete('site_name');

        $created = SettingHistory::action('created')->count();
        $updated = SettingHistory::action('updated')->count();
        $deleted = SettingHistory::action('deleted')->count();

        $this->assertEquals(1, $created);
        $this->assertEquals(1, $updated);
        $this->assertEquals(1, $deleted);
    }

    #[Test]
    public function it_records_history_for_different_models_separately(): void
    {
        $user1 = TestUser::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = TestUser::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        $user1->setSetting('theme', 'dark');
        $user2->setSetting('theme', 'light');

        $user1History = SettingHistory::where('reference_id', $user1->id)->count();
        $user2History = SettingHistory::where('reference_id', $user2->id)->count();

        $this->assertEquals(1, $user1History);
        $this->assertEquals(1, $user2History);
    }

    #[Test]
    public function it_records_history_for_all_value_types(): void
    {
        // String
        $this->manager->set('site_name', 'Test');

        // Boolean
        $this->manager->set('maintenance_mode', true);

        // Integer
        $this->manager->set('max_users', 500);

        // Float
        $this->manager->set('tax_rate', 0.15);

        // Array
        $this->manager->set('allowed_ips', ['127.0.0.1']);

        $count = SettingHistory::where('action', 'created')->count();

        $this->assertEquals(5, $count);
    }

    #[Test]
    public function it_records_timestamps_for_audit_trail(): void
    {
        $this->manager->set('site_name', 'Test');

        $history = SettingHistory::where('key', 'site_name')->first();

        $this->assertNotNull($history->created_at);
        $this->assertInstanceOf(\DateTime::class, $history->created_at);
    }

    #[Test]
    public function it_can_track_full_lifecycle_of_setting(): void
    {
        // Create
        $this->manager->set('site_name', 'Initial');

        // Update multiple times
        $this->manager->set('site_name', 'Updated 1');
        $this->manager->set('site_name', 'Updated 2');

        // Delete
        $this->manager->delete('site_name');

        $lifecycle = SettingHistory::where('key', 'site_name')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertEquals('created', $lifecycle[0]->action);
        $this->assertEquals('updated', $lifecycle[1]->action);
        $this->assertEquals('updated', $lifecycle[2]->action);
        $this->assertEquals('deleted', $lifecycle[3]->action);
    }

    #[Test]
    public function it_handles_batch_operations_in_audit_trail(): void
    {
        $this->manager->setMultiple([
            'site_name' => 'Batch',
            'maintenance_mode' => true,
            'max_users' => 100,
        ]);

        $count = SettingHistory::where('action', 'created')->count();

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_records_audit_for_encrypted_settings(): void
    {
        $this->manager->set('api_key', str_repeat('a', 32));

        $history = SettingHistory::where('key', 'api_key')->first();

        $this->assertNotNull($history);
        $this->assertEquals('created', $history->action);
        // The new_value should be encrypted in the actual database
    }

    #[Test]
    public function it_does_not_record_audit_when_setting_same_value(): void
    {
        // Set initial value - should create audit entry
        $this->manager->set('site_name', 'Same Value');

        $initialCount = SettingHistory::where('key', 'site_name')->count();
        $this->assertEquals(1, $initialCount);

        // Set the same value again - should NOT create audit entry
        $this->manager->set('site_name', 'Same Value');

        $finalCount = SettingHistory::where('key', 'site_name')->count();
        $this->assertEquals(1, $finalCount); // Count should remain the same

        // Verify the single entry is the original 'created' entry
        $history = SettingHistory::where('key', 'site_name')->first();
        $this->assertEquals('created', $history->action);
    }

    #[Test]
    public function it_records_audit_when_setting_different_value(): void
    {
        // Set initial value
        $this->manager->set('site_name', 'First Value');

        $initialCount = SettingHistory::where('key', 'site_name')->count();
        $this->assertEquals(1, $initialCount);

        // Set different value - should create audit entry
        $this->manager->set('site_name', 'Second Value');

        $finalCount = SettingHistory::where('key', 'site_name')->count();
        $this->assertEquals(2, $finalCount); // Should have 2 entries now

        // Verify we have both 'created' and 'updated' entries
        $created = SettingHistory::where('key', 'site_name')->where('action', 'created')->count();
        $updated = SettingHistory::where('key', 'site_name')->where('action', 'updated')->count();

        $this->assertEquals(1, $created);
        $this->assertEquals(1, $updated);
    }

    #[Test]
    public function it_handles_same_value_optimization_for_model_scoped_settings(): void
    {
        // Set initial value for user - should create audit entry
        $this->user->setSetting('theme', 'dark');

        $initialCount = SettingHistory::where('key', 'theme')
            ->where('reference_id', $this->user->id)
            ->count();
        $this->assertEquals(1, $initialCount);

        // Set same value again - should NOT create audit entry
        $this->user->setSetting('theme', 'dark');

        $finalCount = SettingHistory::where('key', 'theme')
            ->where('reference_id', $this->user->id)
            ->count();
        $this->assertEquals(1, $finalCount); // Count should remain the same

        // Set different value - should create audit entry
        $this->user->setSetting('theme', 'light');

        $updatedCount = SettingHistory::where('key', 'theme')
            ->where('reference_id', $this->user->id)
            ->count();
        $this->assertEquals(2, $updatedCount); // Should have 2 entries now
    }

    #[Test]
    public function it_handles_same_value_optimization_in_batch_operations(): void
    {
        // Set initial values
        $this->manager->setMultiple([
            'site_name' => 'Original Site',
            'max_users' => 100,
        ]);

        $initialCount = SettingHistory::where('action', 'created')->count();
        $this->assertEquals(2, $initialCount);

        // Set same values again - should NOT create audit entries
        $this->manager->setMultiple([
            'site_name' => 'Original Site',
            'max_users' => 100,
        ]);

        $finalCount = SettingHistory::where('action', 'created')->count();
        $this->assertEquals(2, $finalCount); // Count should remain the same

        // Set mixed values (one same, one different) - should create 1 audit entry
        $this->manager->setMultiple([
            'site_name' => 'Original Site', // Same value
            'max_users' => 200,            // Different value
        ]);

        $updatedCount = SettingHistory::where('action', 'updated')->count();
        $this->assertEquals(1, $updatedCount); // Only max_users should create audit entry
    }
}
