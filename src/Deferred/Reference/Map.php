<?php

namespace WebTheory\Config\Deferred\Reference;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Map implements DeferredValueInterface
{
    public function __construct(protected array $map)
    {
        //
    }

    public function resolve(ConfigInterface $config): mixed
    {
        return array_map([$config, 'get'], $this->map);
    }
}
