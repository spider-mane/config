<?php

namespace WebTheory\Config\Deferred;

use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Callback implements DeferredValueInterface
{
    /**
     * @var callable
     */
    protected $callback;

    protected array $args;

    public function __construct(callable $callback, mixed ...$args)
    {
        $this->callback = $callback;
        $this->args = $args;
    }

    public function resolve(ConfigInterface $config): mixed
    {
        return ($this->callback)(...[...$this->args, $config]);
    }
}
