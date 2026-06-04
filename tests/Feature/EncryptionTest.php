<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Models\Setting;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class EncryptionTest extends TestCase
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
    public function it_encrypts_values_marked_as_encrypted(): void
    {
        $secretValue = str_repeat('secret', 10);

        $this->manager->set('api_key', $secretValue);

        // Get the raw database value
        $setting = Setting::where('key', 'api_key')->first();
        $storedValue = $setting->value;

        // The stored value should be encrypted (not equal to the original)
        $this->assertNotEquals(json_encode($secretValue), $storedValue);

        // But when we retrieve it through the manager, it should be decrypted
        $retrieved = $this->manager->get('api_key');
        $this->assertEquals($secretValue, $retrieved);
    }

    #[Test]
    public function it_decrypts_values_when_retrieving(): void
    {
        $secretKey = 'my-super-secret-key-that-is-long-enough';

        $this->manager->set('api_key', $secretKey);
        $retrieved = $this->manager->get('api_key');

        $this->assertEquals($secretKey, $retrieved);
    }

    #[Test]
    public function it_does_not_encrypt_non_encrypted_fields(): void
    {
        $this->manager->set('site_name', 'Normal Value');

        $setting = Setting::where('key', 'site_name')->first();
        $storedValue = $setting->value;

        // Non-encrypted field should be stored as-is (just JSON encoded)
        $this->assertEquals(json_encode('Normal Value'), $storedValue);
    }

    #[Test]
    public function it_handles_encrypted_field_updates(): void
    {
        $firstSecret = str_repeat('first', 8);
        $secondSecret = str_repeat('second', 7);

        $this->manager->set('api_key', $firstSecret);
        $this->assertEquals($firstSecret, $this->manager->get('api_key'));

        $this->manager->set('api_key', $secondSecret);
        $this->assertEquals($secondSecret, $this->manager->get('api_key'));
    }

    #[Test]
    public function it_returns_default_on_decryption_failure(): void
    {
        // Manually create a setting with corrupted encrypted data
        Setting::create([
            'key' => 'api_key',
            'value' => 'corrupted_encrypted_data',
            'reference_type' => null,
            'reference_id' => null,
        ]);

        // Should return default value when decryption fails
        $value = $this->manager->get('api_key');

        $this->assertEquals('', $value); // Default for api_key is ''
    }

    #[Test]
    public function it_stores_encrypted_and_non_encrypted_separately(): void
    {
        $this->manager->set('api_key', str_repeat('secret', 8));
        $this->manager->set('site_name', 'Public Site Name');

        $apiKeySetting = Setting::where('key', 'api_key')->first();
        $siteNameSetting = Setting::where('key', 'site_name')->first();

        // API key should be encrypted (longer, contains encryption overhead)
        $this->assertNotEquals(json_encode(str_repeat('secret', 8)), $apiKeySetting->value);

        // Site name should not be encrypted (plain JSON)
        $this->assertEquals(json_encode('Public Site Name'), $siteNameSetting->value);
    }

    #[Test]
    public function it_caches_decrypted_values(): void
    {
        $secretValue = str_repeat('cached-secret', 5);

        $this->manager->set('api_key', $secretValue);

        // Get once to cache
        $value1 = $this->manager->get('api_key');

        // Corrupt the database value
        Setting::where('key', 'api_key')->update(['value' => 'corrupted']);

        // Should still get the cached decrypted value
        $value2 = $this->manager->get('api_key');

        $this->assertEquals($secretValue, $value1);
        $this->assertEquals($secretValue, $value2);
    }

    #[Test]
    public function it_validates_encrypted_fields_before_encryption(): void
    {
        $this->expectException(ValidationException::class);

        // api_key has min:32 rule
        $this->manager->set('api_key', 'too-short');
    }

    #[Test]
    public function it_encrypts_after_validation_passes(): void
    {
        $validKey = str_repeat('a', 32);

        $this->manager->set('api_key', $validKey);

        $setting = Setting::where('key', 'api_key')->first();

        // Should be encrypted in database
        $this->assertNotEquals(json_encode($validKey), $setting->value);

        // But should decrypt correctly
        $this->assertEquals($validKey, $this->manager->get('api_key'));
    }
}
