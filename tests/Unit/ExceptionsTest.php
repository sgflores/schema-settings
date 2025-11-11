<?php

namespace SgFlores\SchemaSetting\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SgFlores\SchemaSetting\Exceptions\InvalidConfigurableException;
use SgFlores\SchemaSetting\Exceptions\InvalidSchemaException;
use SgFlores\SchemaSetting\Exceptions\ReadonlySettingException;
use SgFlores\SchemaSetting\Exceptions\SchemaSettingException;
use SgFlores\SchemaSetting\Exceptions\SettingNotFoundException;

class ExceptionsTest extends TestCase
{
    #[Test]
    public function setting_not_found_exception_has_correct_properties(): void
    {
        $exception = new SettingNotFoundException('test_key', 'global');

        $this->assertEquals('test_key', $exception->key);
        $this->assertEquals('global', $exception->scope);
        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertStringContainsString('test_key', $exception->getMessage());
        $this->assertStringContainsString('global', $exception->getMessage());
    }

    #[Test]
    public function readonly_setting_exception_has_correct_properties(): void
    {
        $exception = new ReadonlySettingException('readonly_key', 'modified');

        $this->assertEquals('readonly_key', $exception->key);
        $this->assertEquals('modified', $exception->operation);
        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('readonly_key', $exception->getMessage());
        $this->assertStringContainsString('modified', $exception->getMessage());
    }

    #[Test]
    public function readonly_setting_exception_supports_different_operations(): void
    {
        $modifyException = new ReadonlySettingException('key', 'modified');
        $deleteException = new ReadonlySettingException('key', 'deleted');

        $this->assertStringContainsString('modified', $modifyException->getMessage());
        $this->assertStringContainsString('deleted', $deleteException->getMessage());
    }

    #[Test]
    public function invalid_schema_exception_has_correct_properties(): void
    {
        $exception = new InvalidSchemaException('Invalid type', 'test_key');

        $this->assertEquals('test_key', $exception->key);
        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Invalid type', $exception->getMessage());
    }

    #[Test]
    public function invalid_schema_exception_works_without_key(): void
    {
        $exception = new InvalidSchemaException('General schema error');

        $this->assertNull($exception->key);
        $this->assertEquals('General schema error', $exception->getMessage());
    }

    #[Test]
    public function invalid_configurable_exception_has_correct_properties(): void
    {
        $exception = new InvalidConfigurableException('App\\InvalidClass');

        $this->assertEquals('App\\InvalidClass', $exception->class);
        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('App\\InvalidClass', $exception->getMessage());
        $this->assertStringContainsString('ConfigurableInterface', $exception->getMessage());
    }

    #[Test]
    public function all_exceptions_extend_base_exception(): void
    {
        $this->assertInstanceOf(SchemaSettingException::class, new SettingNotFoundException('key', 'scope'));
        $this->assertInstanceOf(SchemaSettingException::class, new ReadonlySettingException('key'));
        $this->assertInstanceOf(SchemaSettingException::class, new InvalidSchemaException('message'));
        $this->assertInstanceOf(SchemaSettingException::class, new InvalidConfigurableException('class'));
    }

    #[Test]
    public function all_exceptions_have_status_codes(): void
    {
        $this->assertEquals(404, (new SettingNotFoundException('key', 'scope'))->getStatusCode());
        $this->assertEquals(403, (new ReadonlySettingException('key'))->getStatusCode());
        $this->assertEquals(500, (new InvalidSchemaException('msg'))->getStatusCode());
        $this->assertEquals(500, (new InvalidConfigurableException('class'))->getStatusCode());
    }

    #[Test]
    public function exceptions_can_be_caught_as_base_type(): void
    {
        try {
            throw new SettingNotFoundException('key', 'scope');
        } catch (SchemaSettingException $e) {
            $this->assertInstanceOf(SettingNotFoundException::class, $e);
        }
    }
}
