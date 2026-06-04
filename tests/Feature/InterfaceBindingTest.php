<?php

namespace SgFlores\SchemaSetting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\SchemaSetting\Contracts\SettingsManagerInterface;
use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Tests\Fixtures\TestGlobalSettings;
use SgFlores\SchemaSetting\Tests\TestCase;

class InterfaceBindingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_resolve_settings_manager_from_container(): void
    {
        $manager = app('schema-settings');

        $this->assertInstanceOf(SettingsManager::class, $manager);
    }

    #[Test]
    public function it_can_resolve_via_interface(): void
    {
        $manager = app(SettingsManagerInterface::class);

        $this->assertInstanceOf(SettingsManager::class, $manager);
        $this->assertInstanceOf(SettingsManagerInterface::class, $manager);
    }

    #[Test]
    public function it_returns_same_singleton_instance(): void
    {
        $manager1 = app('schema-settings');
        $manager2 = app('schema-settings');
        $manager3 = app(SettingsManagerInterface::class);

        $this->assertSame($manager1, $manager2);
        $this->assertSame($manager1, $manager3);
    }

    #[Test]
    public function it_can_inject_via_interface_in_constructor(): void
    {
        $service = new class(app(SettingsManagerInterface::class))
        {
            public function __construct(
                public SettingsManagerInterface $settings
            ) {}
        };

        $this->assertInstanceOf(SettingsManagerInterface::class, $service->settings);
        $this->assertInstanceOf(SettingsManager::class, $service->settings);
    }

    #[Test]
    public function interface_binding_works_with_dependency_injection(): void
    {
        $manager = app()->make(SettingsManagerInterface::class);
        $manager->register(TestGlobalSettings::class);

        $schema = $manager->getSchema('global');

        $this->assertNotEmpty($schema);
    }
}
