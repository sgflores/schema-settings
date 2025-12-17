<?php

namespace App\Providers\SchemaSettings;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class UserSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return \App\Models\User::class;
    }

    public static function registerConfigurables(): array
    {
        return [
            // Notification Settings
            ConfigurableItem::make('email_notifications')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Email Notifications')
                ->description('Receive notifications via email'),

            ConfigurableItem::make('timezone')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('UTC')
                ->group('localization')
                ->label('Timezone')
                ->description('Your timezone for date/time display')
                ->rules(['required', 'timezone']),
        ];
    }
}
