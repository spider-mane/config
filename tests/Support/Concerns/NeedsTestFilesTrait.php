<?php

namespace Tests\Support\Concerns;

trait NeedsTestFilesTrait
{
    protected static function getTestPath(string $path): string
    {
        return dirname(__DIR__, 2) . $path;
    }

    protected static function getSupportPath(string $path = ''): string
    {
        return static::getTestPath('/' . basename(dirname(__DIR__)) . $path);
    }
}
