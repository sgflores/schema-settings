<?php

namespace SgFlores\SchemaSetting\Exceptions;

use Exception;

/**
 * SchemaSettingException
 *
 * Base exception class for all exceptions thrown by the Schema Settings package.
 *
 * All package-specific exceptions extend this base class, allowing you to catch
 * any package exception with a single catch block if needed.
 *
 * Specific exception types (SettingNotFoundException, ReadonlySettingException, etc.)
 * should be caught individually for more precise error handling.
 */
class SchemaSettingException extends Exception
{
    //
}
