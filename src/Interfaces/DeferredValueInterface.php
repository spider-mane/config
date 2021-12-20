<?php

namespace WebTheory\Config\Interfaces;

interface DeferredValueInterface
{
    public function defer(ConfigInterface $config);
}
