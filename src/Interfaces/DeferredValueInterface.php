<?php

namespace WebTheory\Config\Interfaces;

interface DeferredValueInterface
{
    public function resolve(ConfigInterface $config);
}
