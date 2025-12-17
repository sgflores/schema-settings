<?php

namespace SgFlores\SchemaSetting\Exceptions;

/**
 * ReadonlySettingException
 *
 * Thrown when attempting to modify or delete a setting marked as readonly.
 *
 * Readonly settings can only be set programmatically during installation,
 * migrations, or seeders. They cannot be changed at runtime for security/stability.
 *
 * HTTP Status Code: 403 (Forbidden)
 *
 * Common Use Cases for Readonly Settings:
 * - Installation date/time
 * - Application version
 * - License keys
 * - System identifiers
 */
class ReadonlySettingException extends SchemaSettingException
{
    /**
     * Create a new ReadonlySettingException instance.
     *
     * @param  string  $key  The readonly setting key
     * @param  string  $operation  The attempted operation ('modified' or 'deleted')
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        public string $key,
        public string $operation = 'modified',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = "Setting '{$key}' is readonly and cannot be {$operation}.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the recommended HTTP status code for API responses.
     *
     * @return int 403 (Forbidden)
     */
    public function getStatusCode(): int
    {
        return 403;
    }
}
