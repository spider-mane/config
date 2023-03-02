<?php

namespace Tests\Support\Concerns;

use DirectoryIterator;

trait UsesTestDataTrait
{
    use NeedsTestFilesTrait;

    protected static function getDataPath(string $file = ''): string
    {
        return static::getSupportPath('/data' . $file);
    }

    protected static function getConfigValues(): array
    {
        $data = [];

        foreach (new DirectoryIterator(static::getDataPath()) as $path) {
            if ($path->isFile()) {
                $data[$path->getBasename('.php')] = require $path->getPathname();
            }
        }

        return $data;
    }

    protected static function getDataValue(string $key, ?array $data = null)
    {
        $data ??= static::getConfigValues();

        foreach (explode('.', $key) as $part) {
            $data = $data[$part];
        }

        return $data;
    }

    protected static function getMainConfigRoot(): string
    {
        return 'data';
    }

    protected static function getDeferrableConfigKey(): string
    {
        return 'data.deferred';
    }

    protected static function getUndefinedConfigKey(): string
    {
        return 'data.undefined';
    }
}
