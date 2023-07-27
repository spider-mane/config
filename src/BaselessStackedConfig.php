<?php

namespace WebTheory\Config;

use WebTheory\Config\Abstracts\AbstractStackedConfig;
use WebTheory\Config\Interfaces\ConfigInterface;

class BaselessStackedConfig extends AbstractStackedConfig implements ConfigInterface
{
    public function __construct(ConfigInterface ...$stack)
    {
        $this->stack = $stack;
    }

    public function set(string $key, mixed $value): void
    {
        $this->stack[0]->set($key, $value);
    }
}
