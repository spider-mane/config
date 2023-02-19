<?php

declare(strict_types=1);

namespace Tests\Suites\Unit;

use Tests\Support\Concerns\UsesTestDataTrait;
use Tests\Support\UnitTestCase;
use WebTheory\Config\Config;

class ConfigTest extends UnitTestCase
{
    use UsesTestDataTrait;

    protected Config $sut;

    public function setUp(): void
    {
        parent::setUp();

        $this->sut = new Config($this->getDataPath());
    }

    /**
     * @test
     */
    public function it_contains_all_provided_data()
    {
        $this->assertSame($this->getFullyResolvedConfigValues(), $this->sut->all());
    }

    /**
     * @test
     * @dataProvider delimitedNotationData
     */
    public function it_retrieves_nested_data_using_delimited_notation($key, $value)
    {
        $this->assertSame($value, $this->sut->get($key));
    }

    public function delimitedNotationData()
    {
        $dotScalar = 'data.scalar';
        $dotArray = 'data.array';

        return [
            'delimiter=dot type=scalar' => [$dotScalar, $this->getDataValue($dotScalar)],
            'delimiter=dot type=array' => [$dotArray, $this->getDataValue($dotArray)],

            'delimiter=slash type=scalar' => ['data/scalar', $this->getDataValue($dotScalar)],
            'delimiter=slash type=array' => ['data/array', $this->getDataValue($dotArray)],
        ];
    }

    /**
     * @test
     * @dataProvider booleanExpectationsData
     */
    public function it_successfully_determines_whether_or_not_it_has_an_entry(string $key, bool $valid)
    {
        $result = $this->sut->has($key);

        $valid
            ? $this->assertTrue($result, 'Failed asserting that key is present')
            : $this->assertFalse($result, 'Failed asserting that key is not present');
    }

    public function booleanExpectationsData()
    {
        return [
            'present=true' => [
                'key' => 'data.scalar',
                'valid' => true,
            ],
            'present=false' => [
                'key' => 'invalid.data',
                'valid' => false,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_can_retrieve_from_a_cascade_of_keys_without_error()
    {
        $invalidKey = 'data.array.sub1a.sub2a.sub3a';
        $validKey = 'data.array.sub1a.sub2a';
        $cascade = [$invalidKey, $validKey];
        $expected = $this->getDataValue($validKey);

        foreach ($cascade as $key) {
            if ($this->sut->has($key)) {
                # Smoke
                $this->assertSame($validKey, $key);

                # Assert
                $this->assertSame($expected, $this->sut->get($key));
            } else {
                # Smoke
                $this->assertSame($invalidKey, $key);
            }
        }
    }

    /**
     * @test
     */
    public function it_returns_an_array_when_requested_value_is_an_array()
    {
        $this->assertIsArray($this->sut->get('data.array'));
    }

    /**
     * @test
     */
    public function it_does_not_cache_provided_default_as_value()
    {
        $key = 'invalid.key';
        $default = $this->unique->word;

        $this->sut->get($key, $this->unique->word);

        $withDefault = $this->sut->get($key, $default);
        $withoutDefault = $this->sut->get($key);

        $this->assertSame($default, $withDefault);
        $this->assertNull($withoutDefault);
    }

    /**
     * @test
     */
    public function it_resolves_deferred_values_on_request()
    {
        $key = $this->getDeferrableConfigKey();
        $deferrable = $this->getDataValue($key);

        $resolved = $deferrable->resolve(new Config($this->getDataPath()));

        $this->assertSame($resolved, $this->sut->get($key));
    }

    /**
     * @test
     * @dataProvider returnedArrayData
     */
    public function it_does_not_include_unresolved_values_when_returning_an_array(
        string $method,
        array $args = [],
        ?string $from = null
    ) {
        $unresolved = $this->getConfigValues();
        $resolved = $this->getFullyResolvedConfigValues();

        if (isset($from)) {
            $resolved = $this->getDataValue($from, $resolved);
        }

        $result = $this->performSystemAction($this->sut, $method, $args);

        # Smoke
        $this->assertNotSame($resolved, $unresolved);

        # Assert
        $this->assertSame($resolved, $result);
    }

    public function returnedArrayData(): array
    {
        return [
            $this->mut('get') => [
                'method' => 'get',
                'args' => ['data'],
                'from' => 'data',
            ],
            $this->mut('all') => [
                'method' => 'all',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider postInitiationSetData
     */
    public function it_includes_values_set_after_initiation_in_a_returned_array(
        string $method,
        array $args = [],
        string $set = null,
        string $find = null
    ) {
        # Arrange
        if (!$set) {
            $set = 'undefined.key';
            $find = 'undefined.key';
        };

        $value = $this->fake->email;

        # Act
        $this->sut->set($set, $value);
        $array = $this->performSystemAction($this->sut, $method, $args);

        # Smoke
        $this->assertIsArray($array, 'value retrieved from provided method must be an array');

        # Assert
        $this->assertSame($this->getDataValue($find, $array), $value);
    }

    public function postInitiationSetData(): array
    {
        return [
            $this->mut('get') => [
                'method' => 'get',
                'args' => ['undefined'],
                'set' => 'undefined.data.key',
                'find' => 'data.key',
            ],
            $this->mut('all') => [
                'method' => 'all',
            ],
        ];
    }
}
