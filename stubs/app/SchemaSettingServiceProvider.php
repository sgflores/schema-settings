<?php

namespace App\SchemaSettings;

use Illuminate\Support\ServiceProvider;
use SgFlores\SchemaSetting\Facades\Settings;

class SchemaSettingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your schema classes here
        Settings::register(GlobalSettings::class);
        Settings::register(UserSettings::class);
    }
}
