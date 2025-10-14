<?php

namespace App\Configurables;

use Illuminate\Support\ServiceProvider;
use SgFlores\SchemaSetting\Facades\Settings;

class ConfigurableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your schema classes here
        Settings::register(GlobalSettings::class);
        Settings::register(UserSettings::class);
    }
}
