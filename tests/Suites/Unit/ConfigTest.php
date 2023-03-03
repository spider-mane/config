<?php

declare(strict_types=1);

namespace Tests\Suites\Unit;

use Error;
use Tests\Support\Concerns\UsesTestDataTrait;
use Tests\Support\UnitTestCase;
use UnexpectedValueException;
use WebTheory\Config\Config;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class ConfigTest extends UnitTestCase
{
    use UsesTestDataTrait;

    protected Config $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new Config($this->getDataPath());
    }

    protected function getFullyResolvedConfigValues(): array
    {
        return $this->resolveDeferredValues($this->getConfigValues());
    }

    protected function resolveDeferredValues(array $config): array
    {
        array_walk_recursive($config, function (&$entry) {
            $entry = $entry instanceof DeferredValueInterface
                ? $entry->resolve(new Config($this->getDataPath()))
                : $entry;
        }, $config);

        return $config;
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
     */
    public function it_defaults_to_an_empty_array_when_instantiated_without_providing_a_value()
    {
        $sut = new Config();

        $result = $sut->all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_by_providing_an_array()
    {
        # Arrange
        $data = $this->getFullyResolvedConfigValues();
        $sut = new Config($data);

        # Act
        $result = $sut->all();

        # Assert
        $this->assertSame($data, $result);
    }

    /**
     * @test
     */
    public function it_returns_expected_value_for_keys_when_constructed_with_an_array()
    {
        $key = 'data.scalar';
        $sut = new Config($this->getFullyResolvedConfigValues());

        $result = $sut->get($key);

        $this->assertSame($this->getDataValue($key), $result);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_by_providing_a_filename()
    {
        $sut = new Config($this->getDataPath('/file.php'));

        $result = $sut->all();

        $this->assertSame($this->getDataValue('file'), $result);
    }

    /**
     * @test
     */
    public function it_throws_an_error_if_file_passed_does_not_return_an_array()
    {
        $this->expectException(Error::class);

        new Config($this->getDataPath('/invalid/notphp'));
    }

    /**
     * @test
     */
    public function it_throws_an_error_if_string_value_passed_is_not_a_valid_directory()
    {
        $this->expectException(UnexpectedValueException::class);

        new Config('not/a/directory');
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
        $invalidKey = 'data.array.array.scalar.sub3a';
        $validKey = 'data.array.array.scalar';
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

    /**
     * @test
     * @dataProvider updatedArrayValueData
     */
    public function it_returns_an_array_with_updated_values_after_it_has_already_been_accessed(string $get, string $set)
    {
        # Arrange
        $value = $this->fake->email;
        $parts = explode('.', $set);
        $array = implode('.', array_slice($parts, 0, -1));
        $key = implode('.', array_slice($parts, -1));

        # Act
        $this->sut->get($get);
        $this->sut->set($set, $value);

        $result = $this->sut->get($array);

        # Assert
        $this->assertSame($value, $result[$key]);
    }

    public function updatedArrayValueData(): array
    {
        return [
            'startDepth=1, updateDepth=0' => [
                'get' => 'data.array',
                'set' => 'data.array',
            ],
            'startDepth=1, updateDepth=1' => [
                'get' => 'data.array',
                'set' => 'data.array.scalar',
            ],
            'startDepth=1, updateDepth=2' => [
                'get' => 'data.array',
                'set' => 'data.array.array.scalar',
            ],
            'startDepth=2, updateDepth=0' => [
                'get' => 'data.array.array',
                'set' => 'data.array.array',
            ],
            'startDepth=2, updateDepth=1' => [
                'get' => 'data.array.array',
                'set' => 'data.array.array.scalar',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_returns_an_array_that_was_set_after_initiation_with_updated_values()
    {
        # Arrange
        $original = $this->unique->word;
        $updated = $this->unique->word;
        $arrayKey = 'data.undefined';
        $itemKey = 'key';
        $configKey = $arrayKey . '.' . $itemKey;

        $array = [
            $itemKey => $original,
        ];

        # Act
        $this->sut->set($arrayKey, $array);

        # Smoke
        $this->assertEquals($original, $this->sut->get($configKey));
        $this->assertNotEquals($original, $updated);

        $this->sut->set($configKey, $updated);
        $result = $this->sut->get($arrayKey);

        # Assert
        $this->assertSame($updated, $result[$itemKey]);
    }

    /**
     * @test
     * @dataProvider subsequentIdenticalRequestsData
     */
    public function it_returns_the_same_scalar_value_for_a_key_on_subsequent_requests(string $key)
    {
        $request1 = $this->sut->get($key);
        $request2 = $this->sut->get($key);

        $this->assertSame($request1, $request2);
    }

    public function subsequentIdenticalRequestsData(): array
    {
        return [
            'provided=scalar' => [
                'get' => 'data.scalar',
            ],
            'provided=deferred' => [
                'get' => 'data.deferred',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_returns_an_array_containing_path_string_and_data_array_of_cached_current_provided_and_resolved_as_debug_info()
    {
        # Arrange
        $path = $this->getDataPath();

        $base = 'debug';
        $currentArray = [$base => $this->getDataValue($base)];

        $toCache = "$base.key1";
        $cachedArray = [$toCache => $this->getDataValue($toCache)];

        $providedArray = $this->getConfigValues();
        $resolvedArray = $this->getFullyResolvedConfigValues();

        # Act
        $this->sut->get($toCache);

        $result = $this->sut->__debugInfo();

        # Assert
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('data', $result);

        $data = $result['data'];

        $this->assertArrayHasKey('cached', $data);
        $this->assertArrayHasKey('current', $data);
        $this->assertArrayHasKey('provided', $data);
        $this->assertArrayHasKey('resolved', $data);

        $this->assertSame($path, $result['path']);
        $this->assertSame($cachedArray, $data['cached']);
        $this->assertSame($currentArray, $data['current']);
        $this->assertEquals($providedArray, $data['provided']);
        $this->assertEquals($resolvedArray, $data['resolved']);
    }
}
