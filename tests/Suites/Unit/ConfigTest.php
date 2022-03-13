<?php

declare(strict_types=1);

namespace Tests\Suites\Unit;

use Tests\Support\TestCase;
use WebTheory\Config\Config;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

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
            'data' => require $this->getDataPath('/data.php'),
        ]);
    }

    protected function resolveDeferredValues(array $config): array
    {
        array_walk_recursive($config, function (&$entry) {
            $entry = $entry instanceof DeferredValueInterface
                ? $entry->defer(new Config($this->getDataPath()))
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
     * @dataProvider dotNotationItems
     */
    public function it_retrieves_nested_data_using_dot_notation($key, $value)
    {
        $this->assertEquals($value, $this->config->get($key));
    }

    public function dotNotationItems()
    {
        $values = $this->getConfigValues();

        return [
            'scalar' => ['data.scalar', $values['data']['scalar']],
            'array' => ['data.array', $values['data']['array']],
        ];
    }

    /**
     * @test
     */
    public function it_can_retrieve_from_a_cascade_of_keys_without_error()
    {
        $values = $this->getConfigValues();
        $nested = $values['data']['array']['sub1a']['sub2a'];
        $cascade = ['data.array.sub1a.sub2a.sub3a', 'data.array.sub1a.sub2a'];

        foreach ($cascade as $key) {
            if ($this->config->has($key)) {
                $this->assertEquals($cascade[1], $key);
                $this->assertEquals($nested, $this->config->get($key));
            } else {
                $this->assertEquals($cascade[0], $key);
            }
        }
    }

    /**
     * @test
     */
    public function it_returns_an_array_when_requested_value_is_an_array()
    {
        $this->assertIsArray($this->config->get('data.array'));
    }
}
