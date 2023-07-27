<?php

namespace Tests\Support\Doubles;

use Tests\Support\Concerns\FakerTrait;

abstract class AbstractStub
{
    use FakerTrait;

    public function __construct()
    {
        $this->initFaker();
    }
}
