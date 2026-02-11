<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class TestGlobalSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return null; // Global scope
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('site_name')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('Test Site')
                ->rules(['required', 'min:3', 'max:100']),

            ConfigurableItem::make('maintenance_mode')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(false),

            ConfigurableItem::make('max_users')
                ->type(ConfigurableItem::TYPE_INTEGER)
                ->default(100)
                ->rules(['integer', 'min:1', 'max:10000']),

            ConfigurableItem::make('tax_rate')
                ->type(ConfigurableItem::TYPE_FLOAT)
                ->default(0.15)
                ->rules(['numeric', 'min:0', 'max:1']),

            ConfigurableItem::make('allowed_ips')
                ->type(ConfigurableItem::TYPE_ARRAY)
                ->default(['127.0.0.1', 'localhost']),

            ConfigurableItem::make('metadata')
                ->type(ConfigurableItem::TYPE_JSON)
                ->default(['version' => '1.0.0']),

            ConfigurableItem::make('installation_date')
                ->type(ConfigurableItem::TYPE_DATETIME)
                ->default(now()->toDateTimeString())
                ->readonly(),

            ConfigurableItem::make('api_key')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('')
                ->encrypted()
                ->rules(['min:32']),

            ConfigurableItem::make('language')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('en')
                ->options(['en', 'es', 'fr', 'de']),

            ConfigurableItem::make('features')
                ->type(ConfigurableItem::TYPE_JSON)
                ->default([])
                ->group('advanced'),

            ConfigurableItem::make('table_labels')
                ->type(ConfigurableItem::TYPE_LONG_TEXT)
                ->default("Table 1\nTable 2\nTable 3")
                ->group('pos')
                ->rules(['required', 'string']),
        ];
    }
}
