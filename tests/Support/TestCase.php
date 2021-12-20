<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    protected function getSupportPath(string $path = '')
    {
        return __DIR__ . $path;
    }
}
