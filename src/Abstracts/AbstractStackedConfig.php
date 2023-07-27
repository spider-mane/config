<?php

namespace WebTheory\Config\Abstracts;

use WebTheory\Config\Interfaces\ConfigInterface;

abstract class AbstractStackedConfig implements ConfigInterface
{
    /**
     * @var array<ConfigInterface>
     */
    protected array $stack;

    public function has(string $key): bool
    {
        foreach ($this->stack as $config) {
            if ($config->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        foreach ($this->stack as $config) {
            if ($config->has($key)) {
                return $config->get($key);
            }
        }

        return $default;
    }

    public function all(): array
    {
        return array_replace_recursive(...array_map(
            fn (ConfigInterface $config) => $config->all(),
            array_reverse($this->stack)
        ));
    }
}
