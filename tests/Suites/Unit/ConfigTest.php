<?php

declare(strict_types=1);

namespace Tests\Suites\Unit;

use Tests\Support\TestCase;
use WebTheory\Config\Config;

class ConfigTest extends TestCase
{
    protected Config $config;

    public function setUp(): void
    {
        $this->config = new Config($this->getDataPath());
    }

    protected function getDataPath(string $file = ''): string
    {
        return $this->getSupportPath('/data' . $file);
    }

    protected function getConfigValues(): array
    {
        return [
            'data' => require $this->getDataPath('/data.php')
        ];
    }

    /**
     * @test
     */
    public function it_contains_provided_non_deferred_data()
    {
        $this->assertEquals($this->getConfigValues(), $this->config->all());
    }
}
