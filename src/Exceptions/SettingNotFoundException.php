<?php

namespace SgFlores\SchemaSetting\Exceptions;

/**
 * SettingNotFoundException
 *
 * Thrown when attempting to access a setting key that doesn't exist in the registered schema.
 *
 * This exception indicates a developer error - the setting was not properly registered
 * via ConfigurableInterface or the key name is misspelled.
 *
 * HTTP Status Code: 404 (Not Found)
 *
 * Common Causes:
 * - Forgot to register the ConfigurableInterface class
 * - Typo in the setting key
 * - Using wrong model scope
 */
class SettingNotFoundException extends SchemaSettingException
{
    /**
     * Create a new SettingNotFoundException instance.
     *
     * @param  string  $key  The setting key that was not found
     * @param  string  $scope  The scope that was searched (e.g., 'global', User::class)
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        public string $key,
        public string $scope,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = "Setting key '{$key}' not found in scope '{$scope}'.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the recommended HTTP status code for API responses.
     *
     * @return int 404 (Not Found)
     */
    public function getStatusCode(): int
    {
        return 404;
    }
}
