<?php

use Illuminate\Support\Facades\Route;
use SgFlores\SchemaSetting\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Schema Settings API Routes
|--------------------------------------------------------------------------
|
| Here are the default routes for the Schema Settings package.
| These routes are automatically loaded by the service provider.
|
*/

// Only register routes if enabled
if (config('schema-settings.routes.enabled', true)) {
    $routeGroup = Route::prefix(config('schema-settings.routes.prefix', 'api/schema-settings'))
        ->name(config('schema-settings.routes.name_prefix', 'settings.'));

    // Apply middleware only if configured
    $middleware = config('schema-settings.routes.middleware');
    if ($middleware) {
        // Handle both string and array middleware
        $middlewareArray = is_array($middleware) ? $middleware : [$middleware];
        $routeGroup->middleware($middlewareArray);
    }

    $routeGroup->group(function () {
        // Default route for getting settings schema with values
        Route::get('/', SettingsController::class)
            ->name('index');
    });
}

