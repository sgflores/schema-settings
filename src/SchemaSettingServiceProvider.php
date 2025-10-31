<?php

namespace SgFlores\SchemaSetting;

use SgFlores\SchemaSetting\Manager\SettingsManager;
use SgFlores\SchemaSetting\Contracts\SettingsManagerInterface;
use SgFlores\SchemaSetting\Console\ListCommand;
use SgFlores\SchemaSetting\Console\GetCommand;
use SgFlores\SchemaSetting\Console\SetCommand;
use SgFlores\SchemaSetting\Console\ClearCacheCommand;
use Illuminate\Support\ServiceProvider;

/**
 * SchemaSettingServiceProvider
 * 
 * Laravel service provider for the Schema Settings package.
 * 
 * Handles:
 * - Publishing configuration files, migrations, and example classes
 * - Registering artisan commands
 * - Binding the SettingsManager to the service container
 * - Binding the SettingsManagerInterface for dependency injection
 * 
 * Auto-discovered by Laravel via composer.json extra.laravel.providers.
 * 
 * @package SgFlores\SchemaSetting
 */
class SchemaSettingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services.
     * 
     * Publishes assets and registers artisan commands.
     * Called after all service providers are registered.
     * 
     * @return void
     */
    public function boot(): void
    {   
        $this->publishAssets();
        $this->loadRoutes();
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListCommand::class,
                GetCommand::class,
                SetCommand::class,
                ClearCacheCommand::class,
            ]);
        }
    }

    /**
     * Publish package assets.
     */
    protected function publishAssets(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../stubs/config/schema-settings.php' => config_path('schema-settings.php'),
        ], 'schema-settings-config');
        
        // Publish configurable examples
        $this->publishes([
            __DIR__.'/../stubs/app' => app_path('Providers/SchemaSettings'),
        ], 'schema-settings-configurables');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../stubs/database/2025_10_13_000000_create_schema_settings_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_schema_settings_table.php'),
            __DIR__.'/../stubs/database/2025_10_13_000001_create_schema_settings_history_table.php' => database_path('migrations/'.date('Y_m_d_His', strtotime('+1 second')).'_create_schema_settings_history_table.php'),
        ], 'schema-settings-migrations');
    }

    /**
     * Load package routes.
     */
    protected function loadRoutes(): void
    {
        if (config('schema-settings.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }

    /**
     * Register package services.
     * 
     * Merges package configuration and binds the SettingsManager to the container.
     * The manager is registered as a singleton to maintain schema state across requests.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../stubs/config/schema-settings.php', 'schema-settings'
        );
        
        // Register the main settings manager as a singleton
        // Using singleton ensures schema registrations persist across the request
        $this->app->singleton('schema-settings', function ($app) {
            return new SettingsManager();
        });

        // Bind the interface to the implementation for dependency injection
        // Allows type-hinting SettingsManagerInterface in constructors
        $this->app->bind(SettingsManagerInterface::class, function ($app) {
            return $app->make('schema-settings');
        });
    }
}