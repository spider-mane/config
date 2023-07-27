<?php

namespace WebTheory\Config;

use Env\Env;
use WebTheory\Config\Deferred\Callback;
use WebTheory\Config\Deferred\Reference\DefaultMap;
use WebTheory\Config\Deferred\Reference\DefaultMix;
use WebTheory\Config\Deferred\Reference\Map;
use WebTheory\Config\Deferred\Reference\Mix;
use WebTheory\Config\Deferred\Reference\Selection;
use WebTheory\Config\Deferred\Reference\Value;

function env(string $name, mixed $default): mixed
{
    return Env::get($name) ?? $default;
}

function get(string $key, $default = null): Value
{
    return new Value($key, $default);
}

function select(string $key, string $selection, $default = null): Selection
{
    return new Selection($key, $selection, $default);
}

function map(array $map): Map
{
    return new Map($map);
}

function map_default(array $map): DefaultMap
{
    return new DefaultMap($map);
}

function mix(array $map, string $symbol = '@'): Mix
{
    return new Mix($map, $symbol);
}

function mix_default(array $map, string $symbol = '@'): DefaultMix
{
    return new DefaultMix($map, $symbol);
}

function call(callable $callback, mixed ...$args): Callback
{
    return new Callback($callback, ...$args);
}
