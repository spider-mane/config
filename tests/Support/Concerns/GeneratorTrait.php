<?php

declare(strict_types=1);

namespace Tests\Support\Concerns;

use ArrayAccess;
use ArrayIterator;
use Generator;
use IteratorAggregate;
use Traversable;

trait GeneratorTrait
{
    /**
     * Create an indexed array of specified count using a callback to generate
     * each entry.
     */
    protected static function dummyList(callable $generator, int $count = 10): array
    {
        return array_map(fn () => $generator(), range(1, $count));
    }

    /**
     * Create an associative array using a callback to generate entries with
     * keys derived from a provided list.
     */
    protected static function dummyMap(callable $generator, array $keys): array
    {
        return array_map(fn () => $generator(), array_flip($keys));
    }

    /**
     * Create an associative array of specified count using callbacks to
     * generate both keys and entries.
     */
    protected static function dummyKeyMap(callable $keyGen, callable $valueGen, int $count = 10): array
    {
        return static::dummyMap($valueGen, static::dummyList($keyGen, $count));
    }

    /**
     * Returns a callable that creates values on demand. Created values can be
     * later referenced by calling the function with the same input value. Call
     * with '*' as argument to get an array containing all values.
     *
     * @return callable(int|string $item): mixed
     */
    protected static function valueFactory(callable ...$generators): callable
    {
        return new class(...$generators) implements ArrayAccess, IteratorAggregate
        {
            protected array $generators;

            protected array $repository = [];

            public function __construct(callable ...$generators)
            {
                $this->generators = $generators;
            }

            public function __invoke(int|string $item = PHP_INT_MAX): mixed
            {
                $generator = $this->generators[array_rand($this->generators)];

                return '*' === $item
                    ? $this->repository
                    : $this->repository[$item] ??= ($generator)($item);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this($offset);
            }

            public function offsetExists(mixed $offset): bool
            {
                return array_key_exists($offset, $this->repository);
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                $this->repository[$offset] = $value;
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($this->repository[$offset]);
            }

            public function getIterator(): Traversable
            {
                return new ArrayIterator($this->repository);
            }
        };
    }

    /**
     * @return callable(int $count): Generator
     */
    protected static function valueGenerator(callable $generator): callable
    {
        return static function (int $count, mixed ...$args) use ($generator): Generator {
            for ($i = 0; $i < $count; $i++) {
                yield $generator(...$args);
            }
        };
    }
}
