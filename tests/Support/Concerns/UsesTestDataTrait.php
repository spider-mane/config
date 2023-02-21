<?php

namespace Tests\Support\Concerns;

use DirectoryIterator;

trait UsesTestDataTrait
{
    protected function getDataPath(string $file = ''): string
    {
        return $this->getSupportPath('/data' . $file);
    }

    protected function getConfigValues(): array
    {
        $data = [];

        foreach (new DirectoryIterator($this->getDataPath()) as $path) {
            if ($path->isFile()) {
                $data[$path->getBasename('.php')] = require $path->getPathname();
            }
        }

        return $data;
    }

    protected function getDataValue(string $key, ?array $data = null)
    {
        $data ??= $this->getConfigValues();

        foreach (explode('.', $key) as $part) {
            $data = $data[$part];
        }

        return $data;
    }

    protected function getMainConfigRoot(): string
    {
        return 'data';
    }

    protected function getDeferrableConfigKey(): string
    {
        return 'data.deferred';
    }

    protected function getUndefinedConfigKey(): string
    {
        return 'data.undefined';
    }
}
