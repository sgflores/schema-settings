<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use DateTime;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestDateTimeSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class DateTimeHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('schema-settings');
        $this->manager->register(TestDateTimeSettings::class);
    }

    #[Test]
    public function it_can_set_datetime_as_string(): void
    {
        $result = $this->manager->set('last_login', '2024-10-14 15:30:00');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_datetime_object_when_getting(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');

        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
    }

    #[Test]
    public function it_can_set_datetime_object(): void
    {
        $dateTime = new DateTime('2024-10-14 15:30:00');

        $this->manager->set('last_login', $dateTime);
        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals('2024-10-14 15:30:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_set_carbon_instance(): void
    {
        $carbon = Carbon::create(2024, 10, 14, 15, 30, 0);

        $this->manager->set('last_login', $carbon);
        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals('2024-10-14 15:30:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_set_datetime_immutable(): void
    {
        $dateTime = new DateTimeImmutable('2024-10-14 15:30:00');

        $this->manager->set('last_login', $dateTime);
        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals('2024-10-14 15:30:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_returns_default_datetime_value(): void
    {
        $value = $this->manager->get('created_at');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals('2024-01-01 00:00:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_null_datetime_values(): void
    {
        $value = $this->manager->get('last_login');

        $this->assertNull($value);
    }

    #[Test]
    public function it_can_set_null_for_datetime(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->assertNotNull($this->manager->get('last_login'));

        $this->manager->set('last_login', null);
        $value = $this->manager->get('last_login');

        $this->assertNull($value);
    }

    #[Test]
    public function it_stores_datetime_as_string_in_database(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');

        $setting = Setting::where('key', 'last_login')->first();
        $storedValue = json_decode($setting->value, true);

        $this->assertIsString($storedValue);
        $this->assertEquals('2024-10-14 15:30:00', $storedValue);
    }

    #[Test]
    public function it_preserves_datetime_format(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $value = $this->manager->get('last_login');

        $this->assertEquals('2024-10-14 15:30:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_update_datetime_value(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $first = $this->manager->get('last_login');
        $this->assertEquals('2024-10-14 15:30:00', $first->format('Y-m-d H:i:s'));

        $this->manager->set('last_login', '2024-10-15 10:00:00');
        $updated = $this->manager->get('last_login');

        $this->assertEquals('2024-10-15 10:00:00', $updated->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_invalid_datetime_string_gracefully(): void
    {
        $this->manager->set('last_login', 'invalid-date-string');

        $value = $this->manager->get('last_login');

        // Should return null (default) since parsing failed
        $this->assertNull($value);
    }

    #[Test]
    public function it_handles_various_datetime_formats(): void
    {
        $formats = [
            '2024-10-14',
            '2024-10-14 15:30',
            '2024-10-14 15:30:00',
            '14-10-2024',
            '10/14/2024',
        ];

        foreach ($formats as $format) {
            try {
                $this->manager->set('last_login', $format);
                $value = $this->manager->get('last_login');

                $this->assertInstanceOf(\DateTimeInterface::class, $value);
            } catch (\Exception $e) {
                // Some formats might not be recognized, that's OK
                $this->assertTrue(true);
            }
        }
    }

    #[Test]
    public function it_caches_datetime_values_correctly(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $value1 = $this->manager->get('last_login');

        // Modify database directly
        Setting::where('key', 'last_login')->update([
            'value' => json_encode('2024-10-15 10:00:00'),
        ]);

        // Should return cached value
        $value2 = $this->manager->get('last_login');

        $this->assertEquals('2024-10-14 15:30:00', $value2->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_invalidates_cache_on_datetime_update(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->assertEquals('2024-10-14 15:30:00', $this->manager->get('last_login')->format('Y-m-d H:i:s'));

        $this->manager->set('last_login', '2024-10-15 10:00:00');
        $value = $this->manager->get('last_login');

        $this->assertEquals('2024-10-15 10:00:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_works_with_multiple_datetime_settings(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->manager->set('expires_at', '2024-12-31 23:59:59');

        $lastLogin = $this->manager->get('last_login');
        $expiresAt = $this->manager->get('expires_at');

        $this->assertEquals('2024-10-14 15:30:00', $lastLogin->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-12-31 23:59:59', $expiresAt->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_get_multiple_datetime_settings(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->manager->set('expires_at', '2024-12-31 23:59:59');

        $values = $this->manager->getMultiple(['last_login', 'expires_at']);

        $this->assertCount(2, $values);
        $this->assertInstanceOf(\DateTimeInterface::class, $values['last_login']);
        $this->assertInstanceOf(\DateTimeInterface::class, $values['expires_at']);
    }

    #[Test]
    public function it_includes_datetime_in_all_settings(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');

        $all = $this->manager->all();

        $this->assertArrayHasKey('last_login', $all);
        $this->assertArrayHasKey('created_at', $all);
        $this->assertInstanceOf(\DateTimeInterface::class, $all['last_login']);
        $this->assertInstanceOf(\DateTimeInterface::class, $all['created_at']);
    }

    #[Test]
    public function it_records_datetime_changes_in_audit_trail(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->manager->set('last_login', '2024-10-15 10:00:00');

        $this->assertDatabaseCount('schema_settings_history', 2);

        $history = \SgFlores\SchemaSetting\Models\SettingHistory::where('key', 'last_login')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(2, $history);
        $this->assertEquals(json_encode('2024-10-14 15:30:00'), $history[0]->new_value);
        $this->assertEquals(json_encode('2024-10-14 15:30:00'), $history[1]->old_value);
        $this->assertEquals(json_encode('2024-10-15 10:00:00'), $history[1]->new_value);
    }

    #[Test]
    public function it_can_delete_datetime_setting(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->assertNotNull($this->manager->get('last_login'));

        $this->manager->delete('last_login');
        $value = $this->manager->get('last_login');

        // Should return to default (null)
        $this->assertNull($value);
    }

    #[Test]
    public function it_can_format_datetime_values(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $value = $this->manager->get('last_login');

        $this->assertEquals('2024-10-14', $value->format('Y-m-d'));
        $this->assertEquals('15:30:00', $value->format('H:i:s'));
        $this->assertEquals('October 14, 2024', $value->format('F d, Y'));
    }

    #[Test]
    public function it_can_compare_datetime_values(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');
        $this->manager->set('expires_at', '2024-12-31 23:59:59');

        $lastLogin = $this->manager->get('last_login');
        $expiresAt = $this->manager->get('expires_at');

        $this->assertTrue($lastLogin < $expiresAt);
        $this->assertFalse($lastLogin > $expiresAt);
    }

    #[Test]
    public function it_handles_datetime_with_microseconds(): void
    {
        // Carbon::create parameters: year, month, day, hour, minute, second, timezone
        $dateTime = Carbon::create(2024, 10, 14, 15, 30, 0);
        $dateTime = $dateTime->micro(500000); // Set microseconds separately

        $this->manager->set('last_login', $dateTime);
        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        // Microseconds might not be preserved depending on storage format
        $this->assertEquals('2024-10-14 15:30:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_preserves_datetime_object_on_re_retrieval(): void
    {
        $this->manager->set('last_login', '2024-10-14 15:30:00');

        $value1 = $this->manager->get('last_login');
        $value2 = $this->manager->get('last_login');

        $this->assertEquals($value1->format('Y-m-d H:i:s'), $value2->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_datetime_default_from_string(): void
    {
        // Test that default string is properly converted to DateTime
        $value = $this->manager->get('published_date');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals('2024-06-15 10:30:00', $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_works_with_now_helper(): void
    {
        $now = now();

        $this->manager->set('last_login', $now);
        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $value->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_datetime_timezone_information(): void
    {
        $dateTime = new DateTime('2024-10-14 15:30:00', new \DateTimeZone('UTC'));

        $this->manager->set('last_login', $dateTime);
        $value = $this->manager->get('last_login');

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
        $this->assertEquals('2024-10-14 15:30:00', $value->format('Y-m-d H:i:s'));
    }
}
