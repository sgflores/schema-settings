<?php

namespace SgFlores\SchemaSetting\Exceptions;

/**
 * InvalidSchemaException
 *
 * Thrown when a ConfigurableItem schema definition is invalid.
 *
 * This exception indicates a developer error in schema definition, such as:
 * - Invalid type specified
 * - Default value type doesn't match declared type
 * - Enum type without enum class
 * - Empty options array
 * - Non-scalar values in options
 *
 * HTTP Status Code: 500 (Internal Server Error)
 *
 * These errors should be caught during development/testing, not in production.
 */
class InvalidSchemaException extends SchemaSettingException
{
    /**
     * Create a new InvalidSchemaException instance.
     *
     * @param  string  $message  The error message describing what's invalid
     * @param  string|null  $key  Optional setting key where the error occurred
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        string $message,
        public ?string $key = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the recommended HTTP status code for API responses.
     *
     * @return int 500 (Internal Server Error)
     */
    public function getStatusCode(): int
    {
        return 500;
    }
}
