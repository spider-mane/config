<?php

namespace Tests\Support\Concerns;

trait AssertiveTrait
{
    protected function assertArrayIsMap(array $array, string $message = ''): void
    {
        $this->assertFalse(
            array_is_list($array),
            $message ?? 'Failed asserting that array is a map.'
        );
    }
}
