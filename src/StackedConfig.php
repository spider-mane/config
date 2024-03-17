<?php

namespace WebTheory\Config;

use WebTheory\Config\Abstracts\AbstractStackedConfig;
use WebTheory\Config\Interfaces\ConfigInterface;

class StackedConfig extends AbstractStackedConfig implements ConfigInterface
{
    protected ConfigInterface $base;

    public function __construct(ConfigInterface ...$stack)
    {
        $this->base = new Config();
        $this->stack = [$this->base, ...$stack];
    }

    public function set(string $key, mixed $value): void
    {
        $this->base->set($key, $value);
    }

    protected function getPrimaryConfig(): ConfigInterface
    {
        return $this->stack[1];
    }
}
