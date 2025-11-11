<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

/**
 * Test Priority Enum (Integer-backed)
 *
 * Used for testing integer-backed enum type handling.
 */
enum TestPriorityEnum: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;
}
