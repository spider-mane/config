<?php

namespace Tests\Suites\Unit\Deferred;

use Closure;
use Tests\Support\TestCase;
use WebTheory\Config\Deferred\Reflection;
use WebTheory\Config\Interfaces\ConfigInterface;

class ReflectionTest extends TestCase
{
    protected Reflection $reflection;
    protected ConfigInterface $stubConfig;

    protected function setUp(): void
    {
        $this->reflection = Reflection::from($this->getReflectionCallback());
    }

    protected function getReflectionCallback(): Closure
    {
        $key = $this->getFakeConfigKey();

        return function () use ($key) {
            /** @var ConfigInterface $this */
            return $this->get($key);
        };
    }

    protected function getFakeConfigKey(): string
    {
        return 'key.nested';
    }

    /**
     * @test
     */
    public function it_returns_value_of_provided_reference()
    {
        //
    }
}
