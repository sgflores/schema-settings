<?php

namespace App\Configurables;

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
            // Appearance Settings
            ConfigurableItem::make('theme')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('light')
                ->group('appearance')
                ->label('Theme')
                ->description('Choose your preferred color theme')
                ->options(['light', 'dark', 'auto']),

            ConfigurableItem::make('sidebar_collapsed')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(false)
                ->group('appearance')
                ->label('Collapse Sidebar')
                ->description('Start with sidebar collapsed'),

            ConfigurableItem::make('items_per_page')
                ->type(ConfigurableItem::TYPE_INTEGER)
                ->default(25)
                ->group('appearance')
                ->label('Items Per Page')
                ->description('Number of items to show per page')
                ->options([10, 25, 50, 100]),

            // Notification Settings
            ConfigurableItem::make('email_notifications')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Email Notifications')
                ->description('Receive notifications via email'),

            ConfigurableItem::make('notify_on_mention')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Notify on Mention')
                ->description('Get notified when someone mentions you'),

            ConfigurableItem::make('notify_on_reply')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(true)
                ->group('notifications')
                ->label('Notify on Reply')
                ->description('Get notified when someone replies to your post'),

            ConfigurableItem::make('digest_frequency')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('daily')
                ->group('notifications')
                ->label('Digest Frequency')
                ->description('How often to receive email digests')
                ->options(['never', 'daily', 'weekly', 'monthly']),

            // Privacy Settings
            ConfigurableItem::make('profile_visibility')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('public')
                ->group('privacy')
                ->label('Profile Visibility')
                ->description('Who can see your profile')
                ->options(['public', 'members', 'private']),

            ConfigurableItem::make('show_email')
                ->type(ConfigurableItem::TYPE_BOOLEAN)
                ->default(false)
                ->group('privacy')
                ->label('Show Email')
                ->description('Display email on your profile'),

            // Language & Localization
            ConfigurableItem::make('language')
                ->type(ConfigurableItem::TYPE_STRING)
                ->default('en')
                ->group('localization')
                ->label('Language')
                ->description('Your preferred language')
                ->options(['en', 'es', 'fr', 'de']),

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
