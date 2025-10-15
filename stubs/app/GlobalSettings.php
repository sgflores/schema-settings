<?php

namespace App\Configurables;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class GlobalSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return null; // Null indicates global scope
    }

    public static function registerConfigurables(): array
    {
        return [
            // Basic Settings
            ConfigurableItem::make('site_name')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('Awesome App')
                ->group('general')
                ->label('Site Name')
                ->description('The name of your application')
                ->rules(['required', 'min:3', 'max:255']),
            
            ConfigurableItem::make('site_description')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('Welcome to our application')
                ->group('general')
                ->label('Site Description')
                ->description('A brief description of your site'),

            // Boolean Settings
            ConfigurableItem::make('maintenance_mode')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(false)
                ->group('general')
                ->label('Maintenance Mode')
                ->description('Enable maintenance mode to prevent user access'),
            
            ConfigurableItem::make('registration_enabled')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('general')
                ->label('Enable Registration')
                ->description('Allow new users to register'),

            // Numeric Settings
            ConfigurableItem::make('max_upload_size')
                ->type(ConfigurableItem::TYPE_INTEGER)
                ->default(10)
                ->group('files')
                ->label('Max Upload Size (MB)')
                ->description('Maximum file upload size in megabytes')
                ->rules(['integer', 'min:1', 'max:100']),

            ConfigurableItem::make('session_lifetime')
                ->type(ConfigurableItem::TYPE_INTEGER)
                ->default(120)
                ->group('security')
                ->label('Session Lifetime (minutes)')
                ->description('How long sessions should last')
                ->rules(['integer', 'min:5']),

            // Array/JSON Settings
            ConfigurableItem::make('allowed_domains')
                ->type(ConfigurableItem::TYPE_ARRAY)
                ->default(['example.com', 'localhost'])
                ->group('security')
                ->label('Allowed Domains')
                ->description('List of allowed domains for CORS'),

            ConfigurableItem::make('social_links')
                ->type(ConfigurableItem::TYPE_JSON)
                ->default([
                    'twitter' => 'https://twitter.com/example',
                    'facebook' => 'https://facebook.com/example',
                ])
                ->group('social')
                ->label('Social Media Links')
                ->description('Links to your social media profiles'),

            // Options/Select Settings
            ConfigurableItem::make('default_language')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('en')
                ->group('localization')
                ->label('Default Language')
                ->description('The default language for the application')
                ->options(['en', 'es', 'fr', 'de']),

            ConfigurableItem::make('date_format')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('Y-m-d')
                ->group('localization')
                ->label('Date Format')
                ->description('Default date format for the application')
                ->options(['Y-m-d', 'd/m/Y', 'm/d/Y']),

            // Encrypted Settings
            ConfigurableItem::make('api_secret_key')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('')
                ->group('api')
                ->label('API Secret Key')
                ->description('Secret key for API authentication')
                ->encrypted()
                ->rules(['required', 'min:32']),

            // Read-only Settings (can only be set programmatically)
            ConfigurableItem::make('installation_date')
                ->type(ConfigurableItem::TYPE_DATETIME)
                ->default(now()->toDateTimeString())
                ->group('system')
                ->label('Installation Date')
                ->description('When the application was installed')
                ->readonly(),

        ];
    }
}
