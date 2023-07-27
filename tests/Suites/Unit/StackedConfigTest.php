<?php

namespace Tests\Suites\Unit;

use PHPUnit\Framework\MockObject\MockObject;
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
}
