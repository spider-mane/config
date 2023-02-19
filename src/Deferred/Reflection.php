<?php

namespace WebTheory\Config\Deferred;

use Closure;
use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Reflection implements DeferredValueInterface
{
    /**
     * @var Closure
     */
    protected $closure;

    protected function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function __invoke(ConfigInterface $config)
    {
        return $this->resolve($config);
    }

    /**
     * Returns the value of the properties sought by the instance
     */
    public function resolve(ConfigInterface $config)
    {
        return $this->closure->call($config);
    }

    /**
     * Creates a new instance from a custom closure. An instance of
     * ConfigInterface will be bound to it in order to retrieve desired values.
     */
    public static function from(Closure $closure): Reflection
    {
        return new static($closure);
    }

    /**
     * Create a new instance that will retrieve a value from a ConfigInterface
     * instance.
     */
    public static function get(string $key, $default = null): Reflection
    {
        return new static(function () use ($key, $default) {
            /** @var ConfigInterface $this */
            return $this->get($key, $default);
        });
    }

    /**
     * Virtually the same as get(), but handles dynamic selection of an endpoint
     * for the user
     */
    public static function select(string $key, string $selection, $default = null): Reflection
    {
        return new static(function () use ($key, $selection, $default) {
            /** @var ConfigInterface $this */
            return $this->get("$key.{$selection}", $default);
        });
    }

    /**
     * Create a new instance that will retrieve multiple values from a
     * ConfigInterface instance as a new set of key, value pairs.
     */
    public static function map(array $map): Reflection
    {
        return new static(function () use ($map) {
            /** @var ConfigInterface $this */
            return array_map([$this, 'get'], $map);
        });
    }

    /**
     * Create a new instance that will retrieve multiple values from a
     * ConfigInterface instance as a new set of key, value pairs. Only keys
     * starting with the provided symbol will have their values retrieved from
     * the ConfigInterface instance.
     */
    public static function mix(array $map, string $symbol = '@'): Reflection
    {
        return new static(function () use ($map, $symbol) {
            /** @var ConfigInterface $this */
            foreach ($map as $key => $value) {
                if (strpos($symbol, $value) !== 0) {
                    continue;
                }

                $map[$key] = $this->get(ltrim($value, $symbol));
            }

            return $map;
        });
    }

    /**
     * Creates a new instance that retrieves multiple values from a
     * ConfigInterface instance as a new set of key, value pairs. Original value
     * must be a non-associative array with the desired ConfigInterface property
     * as the first entry and a default value as the second.
     */
    public static function defaultMap(array $map): Reflection
    {
        return new static(function () use ($map) {
            /** @var ConfigInterface $this */
            foreach ($map as $key => $values) {
                $map[$key] = $this->get($values[0], $values[1]);
            }

            return $map;
        });
    }

    /**
     * Creates a new instance that retrieves multiple values from a
     * ConfigInterface instance as a new set of key, value pairs. Original value
     * must be a non-associative array with the desired ConfigInterface property
     * as the first entry and a default value as the second. Only keys
     * starting with the provided symbol will have their values retrieved from
     * the ConfigInterface instance.
     */
    public static function defaultMix(array $map, string $symbol = '@'): Reflection
    {
        return new static(function () use ($map, $symbol) {
            /** @var ConfigInterface $this */
            foreach ($map as $key => $values) {
                if (strpos($symbol, $key) !== 0) {
                    continue;
                }

                unset($map[$key]);
                $map[ltrim($key, $symbol)] = $this->get($values[0], $values[1]);
            }

            return $map;
        });
    }
}
