<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class TestUserSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return TestUser::class;
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('theme')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('light')
                ->options(['light', 'dark', 'auto'])
                ->group('appearance')
                ->label('Theme Preference')
                ->description('Choose your preferred color theme'),

            ConfigurableItem::make('notifications_enabled')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Enable Notifications')
                ->description('Receive email notifications'),

            ConfigurableItem::make('items_per_page')
                ->type(ConfigurableItem::TYPE_INTEGER)
                ->default(25)
                ->rules(['integer', 'in:10,25,50,100'])
                ->group('appearance'),

            ConfigurableItem::make('timezone')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('UTC')
                ->rules(['required', 'timezone'])
                ->group('localization'),

            ConfigurableItem::make('preferences')
                ->type(ConfigurableItem::TYPE_JSON)
                ->default([]),
        ];
    }
}
