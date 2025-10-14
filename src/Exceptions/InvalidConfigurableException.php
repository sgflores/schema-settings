<?php

namespace SgFlores\SchemaSetting\Exceptions;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;

/**
 * InvalidConfigurableException
 * 
 * Thrown when attempting to register a class that doesn't implement ConfigurableInterface.
 * 
 * This exception indicates a developer error where:
 * - The class passed to register() is not a valid settings class
 * - The class doesn't implement the required ConfigurableInterface
 * 
 * HTTP Status Code: 500 (Internal Server Error)
 * 
 * Solution: Ensure your settings class implements ConfigurableInterface and defines
 * both getKey() and registerConfigurables() methods.
 * 
 * @package SgFlores\SchemaSetting\Exceptions
 */
class InvalidConfigurableException extends SchemaSettingException
{
    /**
     * Create a new InvalidConfigurableException instance.
     * 
     * @param string $class The class name that failed to implement the interface
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        public readonly string $class,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $interface = ConfigurableInterface::class;
        $message = "Class '{$class}' must implement {$interface}.";
        
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

