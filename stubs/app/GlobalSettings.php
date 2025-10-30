<?php

namespace App\Providers\SchemaSettings;

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
        ];
    }
}
