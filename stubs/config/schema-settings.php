<?php
// config/schema-settings.php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings Table Name
    |--------------------------------------------------------------------------
    |
    | The database table name where settings will be stored.
    |
    */
    'table_name' => 'schema_settings',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for settings retrieval.
    |
    */
    'cache' => [
        'enabled' => true,
        'store' => null, // Use default cache store
        'prefix' => 'schema_settings_',
        'ttl' => null, // null = forever
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail
    |--------------------------------------------------------------------------
    |
    | Enable tracking of setting changes for audit purposes.
    |
    */
    'audit' => [
        'enabled' => true,
        'table_name' => 'schema_settings_history',
    ],
];