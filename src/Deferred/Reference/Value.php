<?php

namespace WebTheory\Config\Deferred\Reference;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Value implements DeferredValueInterface
{
    public function __construct(
        protected string $key,
        protected mixed $default = null
    ) {
        //
    }

    public function resolve(ConfigInterface $config): mixed
    {
        return $config->get($this->key, $this->default);
    }
}
