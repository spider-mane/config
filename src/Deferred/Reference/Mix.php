<?php

namespace WebTheory\Config\Deferred\Reference;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Mix implements DeferredValueInterface
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

        foreach ($map as $key => $value) {
            if (strpos($symbol, $value) !== 0) {
                continue;
            }

            $map[$key] = $config->get(ltrim($value, $symbol));
        }

        return $map;
    }
}
