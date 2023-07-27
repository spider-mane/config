<?php

namespace WebTheory\Config\Deferred\Reference;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class DefaultMix implements DeferredValueInterface
{
    public function __construct(
        protected array $map,
        protected string $symbol = '@'
    ) {
        //
    }

    public function resolve(ConfigInterface $config): mixed
    {
        $map = $this->map;
        $symbol = $this->symbol;

        foreach ($map as $key => $values) {
            if (strpos($symbol, $key) !== 0) {
                continue;
            }

            unset($map[$key]);
            $map[ltrim($key, $symbol)] = $config->get($values[0], $values[1]);
        }

        return $map;
    }
}
