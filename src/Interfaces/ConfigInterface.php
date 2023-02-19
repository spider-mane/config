<?php

namespace WebTheory\Config\Interfaces;

interface ConfigInterface
{
    public function get(string $key, $default = null): mixed;

    public function set(string $key, $value): void;

    public function has(string $key): bool;

    public function all(): array;
}
