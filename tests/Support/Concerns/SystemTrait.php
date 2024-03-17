<?php

declare(strict_types=1);

namespace Tests\Support\Concerns;

trait SystemTrait
{
    /**
     * "Method Under Test" template for use as keys in data providers.
     */
    protected static function mut(string $method, string $extra = ''): string
    {
        $method = "method={$method}()";

        return $extra ? "$method, $extra" : $method;
    }

    /**
     * Call a method on an object with provided arguments. Helpful for tests
     * whose actions are defined by data providers.
     */
    protected static function performSystemAction(object $system, string $method, array $args): mixed
    {
        return $system->{$method}(...array_values($args));
    }
}
