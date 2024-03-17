<?php

namespace Tests\Support\Concerns;

trait AssertionsTrait
{
    protected static function assertArrayIsMap(array $array, string $message = ''): void
    {
        static::assertFalse(
            array_is_list($array),
            $message ?: 'Failed asserting that array is a map.'
        );
    }
}
