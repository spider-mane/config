<?php

namespace WebTheory\Config\Deferred\Reference;

class Navigator
{
    /**
     * Create a new instance that will retrieve a value from a ConfigInterface
     * instance.
     */
    public static function get(string $key, $default = null): Value
    {
        return new Value($key, $default);
    }

    /**
     * Virtually the same as get(), but handles dynamic selection of an endpoint
     * for the user
     */
    public static function select(string $key, string $selection, $default = null): Selection
    {
        return new Selection($key, $selection, $default);
    }

    /**
     * Create a new instance that will retrieve multiple values from a
     * ConfigInterface instance as a new set of key, value pairs.
     */
    public static function map(array $map): Map
    {
        return new Map($map);
    }

    /**
     * Create a new instance that will retrieve multiple values from a
     * ConfigInterface instance as a new set of key, value pairs. Only keys
     * starting with the provided symbol will have their values retrieved from
     * the ConfigInterface instance.
     */
    public static function mix(array $map, string $symbol = '@'): Mix
    {
        return new Mix($map, $symbol);
    }

    /**
     * Creates a new instance that retrieves multiple values from a
     * ConfigInterface instance as a new set of key, value pairs. Original value
     * must be a non-associative array with the desired ConfigInterface property
     * as the first entry and a default value as the second.
     */
    public static function defaultMap(array $map): DefaultMap
    {
        return new DefaultMap($map);
    }

    /**
     * Creates a new instance that retrieves multiple values from a
     * ConfigInterface instance as a new set of key, value pairs. Original value
     * must be a non-associative array with the desired ConfigInterface property
     * as the first entry and a default value as the second. Only keys
     * starting with the provided symbol will have their values retrieved from
     * the ConfigInterface instance.
     */
    public static function defaultMix(array $map, string $symbol = '@'): DefaultMix
    {
        return new DefaultMix($map, $symbol);
    }
}
