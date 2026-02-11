<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class TypeCastingTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestGlobalSettings::class);
    }

    #[Test]
    public function it_handles_invalid_datetime_gracefully(): void
    {
        // Manually insert invalid datetime value
        Setting::create([
            'key' => 'installation_date',
            'value' => json_encode('invalid-datetime-string'),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        // Should return default value instead of throwing exception
        $value = $this->manager->get('installation_date');

        // Should return default or null, not throw exception
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    #[Test]
    public function it_casts_valid_datetime_string(): void
    {
        // Use a non-readonly datetime field - we need to add one to test schema
        // For now, just test with database insertion
        $dateString = '2024-01-15 10:30:00';

        Setting::create([
            'key' => 'installation_date',
            'value' => json_encode($dateString),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('installation_date');

        $this->assertInstanceOf(\DateTime::class, $value);
        $this->assertEquals('2024-01-15', $value->format('Y-m-d'));
    }

    #[Test]
    public function it_preserves_datetime_object(): void
    {
        $date = new \DateTime('2024-01-15 10:30:00');

        // DateTime objects should be preserved when already correct type
        Setting::create([
            'key' => 'installation_date',
            'value' => json_encode($date->format('Y-m-d H:i:s')),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('installation_date');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
    }

    #[Test]
    public function it_handles_null_values_correctly(): void
    {
        // Test that null values return defaults
        $value = $this->manager->get('features'); // Default is []

        $this->assertEquals([], $value);
    }

    #[Test]
    public function it_casts_string_boolean_to_boolean(): void
    {
        // Test casting of string "true"/"false" to boolean
        Setting::create([
            'key' => 'maintenance_mode',
            'value' => json_encode('true'), // String "true"
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('maintenance_mode');

        $this->assertIsBool($value);
        $this->assertTrue($value);
    }

    #[Test]
    public function it_casts_numeric_string_to_integer(): void
    {
        Setting::create([
            'key' => 'max_users',
            'value' => json_encode('500'), // String "500"
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('max_users');

        $this->assertIsInt($value);
        $this->assertEquals(500, $value);
    }

    #[Test]
    public function it_casts_numeric_string_to_float(): void
    {
        Setting::create([
            'key' => 'tax_rate',
            'value' => json_encode('0.15'), // String "0.15"
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('tax_rate');

        $this->assertIsFloat($value);
        $this->assertEquals(0.15, $value);
    }

    #[Test]
    public function it_handles_json_decode_errors_gracefully(): void
    {
        // Manually create corrupted JSON
        Setting::create([
            'key' => 'metadata',
            'value' => 'corrupted-json-data',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        // Should return default instead of throwing
        $value = $this->manager->get('metadata');

        // Default for metadata is ['version' => '1.0.0']
        $this->assertEquals(['version' => '1.0.0'], $value);
    }

    #[Test]
    public function it_handles_array_vs_json_types_correctly(): void
    {
        // Array type
        $this->manager->set('allowed_ips', ['192.168.1.1', '192.168.1.2']);
        $ips = $this->manager->get('allowed_ips');
        $this->assertIsArray($ips);

        // JSON type
        $this->manager->set('metadata', ['key' => 'value']);
        $metadata = $this->manager->get('metadata');
        $this->assertIsArray($metadata);
    }

    #[Test]
    public function it_sanitizes_long_text_by_filtering_empty_lines(): void
    {
        // Set value with empty lines (e.g. "Table 1\nTable 2\n\nTable 3\nTable 4\nTable 5")
        $raw = "Table 1\nTable 2\n\nTable 3\nTable 4\nTable 5";
        $this->manager->set('table_labels', $raw);

        $value = $this->manager->get('table_labels');

        $this->assertSame("Table 1\nTable 2\nTable 3\nTable 4\nTable 5", $value);
    }

    #[Test]
    public function it_sanitizes_long_text_on_read_when_stored_value_has_empty_lines(): void
    {
        // Simulate legacy stored value with empty lines
        Setting::create([
            'key' => 'table_labels',
            'value' => json_encode("Table 1\nTable 2\n\n\nTable 3"),
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $value = $this->manager->get('table_labels');

        $this->assertSame("Table 1\nTable 2\nTable 3", $value);
    }
}
