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

    /**
     * @test
     */
    public function it_retrieves_nested_data_using_dot_notation()
    {
        $values = $this->getConfigValues();
        $nested = $values['data']['key4']['sub1a']['sub2a'];

        $this->assertEquals($nested, $this->config->get('data.key4.sub1a.sub2a'));
    }

    /**
     * @test
     */
    public function it_can_retrieve_from_a_cascade_of_keys_without_error()
    {
        $values = $this->getConfigValues();
        $nested = $values['data']['key4']['sub1a']['sub2a'];
        $cascade = ['data.key4.sub1a.sub2a.sub3a', 'data.key4.sub1a.sub2a'];

        foreach ($cascade as $key) {
            if ($this->config->has($key)) {
                $this->assertEquals($cascade[1], $key);
                $this->assertEquals($nested, $this->config->get($key));
            } else {
                $this->assertEquals($cascade[0], $key);
            }
        }
    }
}
