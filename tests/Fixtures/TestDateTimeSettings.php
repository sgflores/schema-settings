<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class TestDateTimeSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return 'global';
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('last_login')
                ->type(ConfigurableItem::TYPE_DATETIME)
                ->default(null)
                ->label('Last Login')
                ->description('Last login timestamp'),

            ConfigurableItem::make('created_at')
                ->type(ConfigurableItem::TYPE_DATETIME)
                ->default('2024-01-01 00:00:00')
                ->label('Created At')
                ->description('Creation timestamp'),

            ConfigurableItem::make('expires_at')
                ->type(ConfigurableItem::TYPE_DATETIME)
                ->default(null)
                ->rules(['date'])
                ->label('Expires At')
                ->description('Expiration timestamp'),

            ConfigurableItem::make('published_date')
                ->type(ConfigurableItem::TYPE_DATETIME)
                ->default('2024-06-15 10:30:00')
                ->label('Published Date')
                ->description('Publication date'),
        ];
    }
}
