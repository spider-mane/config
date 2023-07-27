<?php

namespace Tests\Support\Doubles;

use WebTheory\Config\Interfaces\ConfigInterface;

class ConfigStub extends AbstractStub implements ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->unique->sentence();
    }

    public function has(string $key): bool
    {
        return $this->fake->boolean();
    }

    public function all(): array
    {
        return [];
    }

    public function set(string $key, mixed $value): void
    {
        return;
    }
}
