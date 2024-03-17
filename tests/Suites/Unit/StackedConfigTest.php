<?php

namespace Tests\Suites\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Tests\Support\UnitTestCase;
use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\StackedConfig;

class StackedConfigTest extends UnitTestCase
{
    protected StackedConfig $sut;

    /**
     * @var ConfigInterface&MockObject
     */
    protected ConfigInterface $config1;

    /**
     * @var ConfigInterface&MockObject
     */
    protected ConfigInterface $config2;

    /**
     * @var ConfigInterface&MockObject
     */
    protected ConfigInterface $config3;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = $this->getMockBuilder(ConfigInterface::class);

        $this->sut = new StackedConfig(
            $this->config1 = $builder->getMock(),
            $this->config2 = $builder->getMock(),
            $this->config3 = $builder->getMock(),
        );
    }

    /**
     * @test
     */
    public function it_prioritizes_by_order_added_when_returning_merged_array()
    {
        # Arrange
        $this->config1->method('all')->willReturn($config1 = [
            'shared1' => $this->unique->sentence(),
        ]);

        $this->config2->method('all')->willReturn($config2 = [
            'shared1' => $this->unique->sentence(),
            'shared2' => $this->unique->sentence(),
        ]);

        $this->config3->method('all')->willReturn([
            'shared2' => $this->unique->sentence(),
        ]);

        # Act
        $result = $this->sut->all();

        # Assert
        $this->assertSame($config1['shared1'], $result['shared1']);
        $this->assertSame($config2['shared2'], $result['shared2']);
    }

    /**
     * @test
     * @dataProvider mergedDataProvidedData
     */
    public function it_merges_data_according_to_specifications(array $data, mixed $expected, bool $canonicalize = false)
    {
        # Arrange
        $this->sut = new StackedConfig(...$this->buildGettableStack($data));

        # Act
        $result = $this->sut->get('base');

        # Assert
        if ($canonicalize) {
            $this->assertEqualsCanonicalizing($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * @return array<ConfigInterface>
     */
    protected function buildGettableStack(array $data, string $key = 'base', ObjectProphecy $stub = null): array
    {
        $stack = [];
        $base = $key;

        foreach ($data as $entry => $value) {
            if ($stub) {
                $config = $stub;
                $base = "{$key}.{$entry}";
            } else {
                $config = $this->prophesize(ConfigInterface::class);
                $stack[] = $config->reveal();

                $config->has(Argument::any())->willReturn(false);
                $config->all(Argument::any())->willReturn($value);
            }

            $config->has($base)->willReturn(true);
            $config->get($base)->willReturn($value);

            if (is_array($value) && !array_is_list($value)) {
                $this->buildGettableStack($value, $base, $config);
            }
        }

        return $stack;
    }

    public function mergedDataProvidedData(): array
    {
        $this->initFaker();

        $values = $this->uniqueValueFactory('streetName', 'sentence');

        return [
            'input=single/list/single, output=list<@merged>' => [
                'data' => [
                    $values('entry-1'),
                    [$values('entry-2')],
                    $values('entry-3'),
                ],
                'expected' => [
                    $values('entry-1'),
                    $values('entry-2'),
                    $values('entry-3'),
                ],
            ],
            'input=single/map/list, output=single<@first>' => [
                'data' => [
                    $values('entry-1'),
                    ['key1' => $values()],
                    [$values()],
                ],
                'expected' => $values('entry-1'),
            ],
            'input=map/map/map, output=map<@merged-deep>' => [
                'data' => [
                    [
                        'key2' => $values('entry-1'),
                        'list' => $values('list-item-1'),
                    ],
                    [
                        'key2' => $values(),
                        'key3' => $values('entry-2'),
                        'list' => [
                            $values('list-item-2'),
                            $values('list-item-3'),
                        ],
                    ],
                    [
                        'key1' => $values('entry-3'),
                        'key2' => $values(),
                        'key3' => $values(),
                        'list' => [
                            $values('list-item-4'),
                            $values('list-item-5'),
                            $values('list-item-6'),
                        ],
                    ],
                ],
                'expected' => [
                    'key2' => $values('entry-1'),
                    'key3' => $values('entry-2'),
                    'key1' => $values('entry-3'),
                    'list' => [
                        $values('list-item-1'),
                        $values('list-item-2'),
                        $values('list-item-3'),
                        $values('list-item-4'),
                        $values('list-item-5'),
                        $values('list-item-6'),
                    ],
                ],
                'canonicalize' => true,
            ],
        ];
    }
}
