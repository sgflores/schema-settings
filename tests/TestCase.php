<?php

namespace SgFlores\SchemaSetting\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use SgFlores\SchemaSetting\SchemaSettingServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load helper functions manually for tests
        require_once __DIR__.'/../src/helpers.php';

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Create users table for testing model-scoped settings
        $this->createTestTables();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SchemaSettingServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set APP_KEY for encryption tests
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Setup schema-settings config
        $app['config']->set('schema-settings.table_name', 'schema_settings');
        $app['config']->set('schema-settings.cache.enabled', true);
        $app['config']->set('schema-settings.cache.prefix', 'test_settings_');
        $app['config']->set('schema-settings.cache.ttl', null);
        $app['config']->set('schema-settings.audit.enabled', true);
        $app['config']->set('schema-settings.audit.table_name', 'schema_settings_history');
    }

    protected function createTestTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
}
