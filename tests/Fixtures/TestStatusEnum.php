<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

/**
 * Test Status Enum
 * 
 * Used for testing enum type handling in schema settings.
 */
enum TestStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Archived = 'archived';
}

