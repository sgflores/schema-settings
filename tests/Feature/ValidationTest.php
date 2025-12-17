<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUser;
use SgFlores\SchemaSetting\Tests\Fixtures\TestUserSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class ValidationTest extends TestCase
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
    public function it_validates_required_rule(): void
    {
        $this->expectException(ValidationException::class);

        // site_name has required rule
        $this->manager->set('site_name', '');
    }

    #[Test]
    public function it_validates_min_length_rule(): void
    {
        $this->expectException(ValidationException::class);

        // site_name has min:3 rule
        $this->manager->set('site_name', 'AB');
    }

    #[Test]
    public function it_validates_max_length_rule(): void
    {
        $this->expectException(ValidationException::class);

        // site_name has max:100 rule
        $this->manager->set('site_name', str_repeat('A', 101));
    }

    #[Test]
    public function it_validates_integer_min_rule(): void
    {
        $this->expectException(ValidationException::class);

        // max_users has min:1 rule
        $this->manager->set('max_users', 0);
    }

    #[Test]
    public function it_validates_integer_max_rule(): void
    {
        $this->expectException(ValidationException::class);

        // max_users has max:10000 rule
        $this->manager->set('max_users', 10001);
    }

    #[Test]
    public function it_validates_float_min_rule(): void
    {
        $this->expectException(ValidationException::class);

        // tax_rate has min:0 rule
        $this->manager->set('tax_rate', -0.1);
    }

    #[Test]
    public function it_validates_float_max_rule(): void
    {
        $this->expectException(ValidationException::class);

        // tax_rate has max:1 rule
        $this->manager->set('tax_rate', 1.1);
    }

    #[Test]
    public function it_validates_options_rule(): void
    {
        $this->expectException(ValidationException::class);

        // language has options: ['en', 'es', 'fr', 'de']
        $this->manager->set('language', 'invalid_language');
    }

    #[Test]
    public function it_accepts_valid_options(): void
    {
        $this->manager->set('language', 'en');
        $this->assertEquals('en', $this->manager->get('language'));

        $this->manager->set('language', 'es');
        $this->assertEquals('es', $this->manager->get('language'));

        $this->manager->set('language', 'fr');
        $this->assertEquals('fr', $this->manager->get('language'));

        $this->manager->set('language', 'de');
        $this->assertEquals('de', $this->manager->get('language'));
    }

    #[Test]
    public function it_validates_timezone_rule(): void
    {
        $this->expectException(ValidationException::class);

        // timezone has 'timezone' rule
        $this->user->setSetting('timezone', 'Invalid/Timezone');
    }

    #[Test]
    public function it_accepts_valid_timezone(): void
    {
        $this->user->setSetting('timezone', 'America/New_York');
        $this->assertEquals('America/New_York', $this->user->setting('timezone'));

        $this->user->setSetting('timezone', 'Europe/London');
        $this->assertEquals('Europe/London', $this->user->setting('timezone'));
    }

    #[Test]
    public function it_validates_in_rule_for_integers(): void
    {
        $this->expectException(ValidationException::class);

        // items_per_page has in:10,25,50,100 rule
        $this->user->setSetting('items_per_page', 75);
    }

    #[Test]
    public function it_accepts_valid_integer_from_in_rule(): void
    {
        $this->user->setSetting('items_per_page', 10);
        $this->assertEquals(10, $this->user->setting('items_per_page'));

        $this->user->setSetting('items_per_page', 25);
        $this->assertEquals(25, $this->user->setting('items_per_page'));

        $this->user->setSetting('items_per_page', 50);
        $this->assertEquals(50, $this->user->setting('items_per_page'));

        $this->user->setSetting('items_per_page', 100);
        $this->assertEquals(100, $this->user->setting('items_per_page'));
    }

    #[Test]
    public function it_validates_min_rule_for_encrypted_fields(): void
    {
        $this->expectException(ValidationException::class);

        // api_key has min:32 rule
        $this->manager->set('api_key', 'short');
    }

    #[Test]
    public function it_accepts_valid_encrypted_field(): void
    {
        $validKey = str_repeat('a', 32);

        $this->manager->set('api_key', $validKey);
        $retrieved = $this->manager->get('api_key');

        $this->assertEquals($validKey, $retrieved);
    }

    #[Test]
    public function it_validates_model_scoped_settings(): void
    {
        $this->expectException(ValidationException::class);

        // theme has options: ['light', 'dark', 'auto']
        $this->user->setSetting('theme', 'invalid_theme');
    }

    #[Test]
    public function it_accepts_valid_model_scoped_setting(): void
    {
        $this->user->setSetting('theme', 'dark');
        $this->assertEquals('dark', $this->user->setting('theme'));
    }

    #[Test]
    public function it_validates_multiple_rules_on_single_field(): void
    {
        // site_name has ['required', 'min:3', 'max:100']

        // Test required
        try {
            $this->manager->set('site_name', '');
            $this->fail('Expected ValidationException for empty value');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Test min
        try {
            $this->manager->set('site_name', 'AB');
            $this->fail('Expected ValidationException for too short value');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Test max
        try {
            $this->manager->set('site_name', str_repeat('A', 101));
            $this->fail('Expected ValidationException for too long value');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Test valid value
        $this->manager->set('site_name', 'Valid Site Name');
        $this->assertEquals('Valid Site Name', $this->manager->get('site_name'));
    }

    #[Test]
    public function it_does_not_validate_when_no_rules_defined(): void
    {
        // features has no validation rules

        $this->manager->set('features', ['any' => 'value']);
        $this->assertEquals(['any' => 'value'], $this->manager->get('features'));

        $this->manager->set('features', []);
        $this->assertEquals([], $this->manager->get('features'));
    }

    #[Test]
    public function it_provides_validation_error_messages(): void
    {
        try {
            $this->manager->set('site_name', 'AB');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();

            $this->assertArrayHasKey('site_name', $errors);
            $this->assertIsArray($errors['site_name']);
        }
    }

    #[Test]
    public function it_validates_before_storing_to_database(): void
    {
        try {
            $this->manager->set('language', 'invalid');
        } catch (ValidationException $e) {
            // Validation failed, so nothing should be in database
        }

        $this->assertDatabaseMissing('schema_settings', [
            'key' => 'language',
            'value' => json_encode('invalid'),
        ]);
    }

    #[Test]
    public function it_validates_on_update_not_just_create(): void
    {
        // Set valid value first
        $this->manager->set('language', 'en');

        // Try to update with invalid value
        $this->expectException(ValidationException::class);

        $this->manager->set('language', 'invalid');
    }
}
