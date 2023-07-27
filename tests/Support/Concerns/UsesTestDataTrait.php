<?php

namespace Tests\Support\Concerns;

use Tests\Support\Doubles\ConfigStub;
use Tests\Support\Doubles\DeferredValueStub;
use WebTheory\Config\Interfaces\DeferredValueInterface;

trait UsesTestDataTrait
{
    use NeedsTestFilesTrait;
    use FakerTrait;

    protected static function getDataPath(string $file = ''): string
    {
        return static::getSupportPath('/data' . $file);
    }

    protected static function getConfigValues(array $extra = []): array
    {
        static $data;

        $unique = static::createFaker()->unique();

        $data ??= array_map(
            fn () => [
                'key1' => $unique->address(),
                'key2' => $unique->streetName(),
                'scalar' => $unique->word(),
                'array' => [
                    'scalar' => $unique->sentence(),
                    'array' => [
                        'scalar' => $unique->colorName(),
                    ],
                ],
                'deferred' => new DeferredValueStub(),
            ],
            array_flip(['entry1', 'entry2', 'entry3'])
        );

        return array_merge_recursive($data, $extra);
    }

    protected static function getEntryValue(string $key, ?array $data = null): mixed
    {
        $data ??= static::getConfigValues();

        foreach (explode('.', $key) as $part) {
            $data = $data[$part];
        }

        return $data;
    }

    protected static function getResolvedConfigValues(array $extra = []): array
    {
        return static::resolveDeferredValues(static::getConfigValues($extra));
    }

    protected static function getResolvedEntryValue(string $key, ?array $data = null): mixed
    {
        return static::resolveDeferredValues(
            static::getEntryValue($key, $data)
        );
    }

    protected static function resolveDeferredValues(array $config): array
    {
        $stub = new ConfigStub();

        array_walk_recursive($config, function (&$entry) use ($stub) {
            $entry = $entry instanceof DeferredValueInterface
                ? $entry->resolve($stub)
                : $entry;
        }, $config);

        return $config;
    }
}
