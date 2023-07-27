<?php

namespace Tests\Support\Doubles;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class DeferredValueStub extends AbstractStub implements DeferredValueInterface
{
    public function resolve(ConfigInterface $config): mixed
    {
        static $val;

        return $val ??= $this->unique->sentence();
    }
}
