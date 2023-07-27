<?php

namespace WebTheory\Config\Deferred\Reference;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class DefaultMap implements DeferredValueInterface
{
    public function __construct(protected array $map)
    {
        //
    }

    public function resolve(ConfigInterface $config): mixed
    {
        $map = $this->map;

        foreach ($map as $key => $values) {
            $map[$key] = $config->get($values[0], $values[1] ?? null);
        }

        return $map;
    }
}
