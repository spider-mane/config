<?php

declare(strict_types=1);

namespace Tests\Suites\Unit;

use Tests\Support\TestCase;
use WebTheory\Config\Config;
use WebTheory\Config\Interfaces\DeferredValueInterface;

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
        return $this->resolveDeferredValues([
            'data' => require $this->getDataPath('/data.php')
        ]);
    }

    protected function resolveDeferredValues(array $config): array
    {
        array_walk_recursive($config, function (&$entry) {
            $entry = $entry instanceof DeferredValueInterface
                ? $entry->defer($this->config)
                : $entry;
        }, $config);

        return $config;
    }

    /**
     * @test
     */
    public function it_contains_provided_data()
    {
        $this->assertEquals($this->getConfigValues(), $this->config->all());
    }
}