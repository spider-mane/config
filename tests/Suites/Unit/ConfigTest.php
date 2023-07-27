<?php

declare(strict_types=1);

namespace Tests\Suites\Unit;

use Error;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\Concerns\UsesTestDataTrait;
use Tests\Support\UnitTestCase;
use UnexpectedValueException;
use WebTheory\Config\Config;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class ConfigTest extends UnitTestCase
{
    use UsesTestDataTrait;

    protected const DEFERRABLE = DeferredValueInterface::class;

    protected Config $sut;

    /**
     * @var DeferredValueInterface&MockObject
     */
    protected DeferredValueInterface $deferred;

    public static function getFileData(string $file): array
    {
        return static::getEntryValue(basename($file, '.php'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->deferred = $this->getDeferredMock();

        $this->sut = new Config($this->getConfigValues());
    }

    /**
     * @return DeferredValueInterface&MockObject
     */
    protected function getDeferredMock(): MockObject
    {
        // @phpstan-ignore-next-line
        return $this->getMockBuilder(static::DEFERRABLE)->getMock();
    }

    /**
     * @test
     */
    public function it_contains_all_provided_data()
    {
        $this->assertEquals($this->getResolvedConfigValues(), $this->sut->all());
    }

    /**
     * @test
     */
    public function it_defaults_to_an_empty_array_when_instantiated_without_providing_a_value()
    {
        $this->sut = new Config();

        $result = $this->sut->all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_by_providing_an_array()
    {
        # Arrange
        $data = $this->getResolvedConfigValues();

        $this->sut = new Config($data);

        # Act
        $result = $this->sut->all();

        # Assert
        $this->assertSame($data, $result);
    }

    /**
     * @test
     */
    public function it_returns_expected_value_for_keys_when_constructed_with_an_array()
    {
        $key = 'entry1.scalar';
        $sut = new Config($this->getConfigValues());

        $result = $sut->get($key);

        $this->assertSame($this->getEntryValue($key), $result);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_by_providing_a_filename()
    {
        $expected = static::getResolvedEntryValue('entry1');

        $this->sut = new Config($this->getDataPath('/entry1.php'));

        $result = $this->sut->all();

        $this->assertSame($expected, $result);
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
        $dotScalar = 'entry1.scalar';
        $dotArray = 'entry1.array';

        return [
            'delimiter=dot type=scalar' => [$dotScalar, $this->getEntryValue($dotScalar)],
            'delimiter=dot type=array' => [$dotArray, $this->getEntryValue($dotArray)],

            'delimiter=slash type=scalar' => ['entry1/scalar', $this->getEntryValue($dotScalar)],
            'delimiter=slash type=array' => ['entry1/array', $this->getEntryValue($dotArray)],
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
                'key' => 'entry1.scalar',
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
        $invalidKey = 'entry1.array.array.scalar.sub3a';
        $validKey = 'entry1.array.array.scalar';
        $cascade = [$invalidKey, $validKey];
        $expected = $this->getEntryValue($validKey);

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
        $this->assertIsArray($this->sut->get('entry1.array'));
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
        $key = 'entry1.deferred';
        $value = $this->unique->sentence();

        $data = $this->getConfigValues();
        $data['entry1']['deferred'] = $this->deferred;

        $this->deferred->method('resolve')->willReturn($value);

        $this->sut = new Config($data);

        $this->assertSame($value, $this->sut->get($key));
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
        $unresolved = $this->getConfigValues([
            'deferred' => $this->deferred,
        ]);

        $resolved = $this->resolveDeferredValues($unresolved);

        if (isset($from)) {
            $resolved = $this->getEntryValue($from, $resolved);
        }

        $this->sut = new Config($unresolved);

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
                'args' => ['deferred'],
                'from' => 'deferred',
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
        $result = $this->performSystemAction($this->sut, $method, $args);

        # Smoke
        $this->assertIsArray($result, 'value retrieved from provided method must be an array');

        # Assert
        $this->assertSame($this->getEntryValue($find, $result), $value);
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
                'get' => 'entry1.array',
                'set' => 'entry1.array',
            ],
            'startDepth=1, updateDepth=1' => [
                'get' => 'entry1.array',
                'set' => 'entry1.array.scalar',
            ],
            'startDepth=1, updateDepth=2' => [
                'get' => 'entry1.array',
                'set' => 'entry1.array.array.scalar',
            ],
            'startDepth=2, updateDepth=0' => [
                'get' => 'entry1.array.array',
                'set' => 'entry1.array.array',
            ],
            'startDepth=2, updateDepth=1' => [
                'get' => 'entry1.array.array',
                'set' => 'entry1.array.array.scalar',
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
        $arrayKey = 'entry1.undefined';
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
                'get' => 'entry1.scalar',
            ],
            'provided=deferred' => [
                'get' => 'entry1.deferred',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider debugInfoData
     */
    public function it_returns_an_array_containing_path_string_and_data_array_of_cached_stored_provided_and_resolved_as_debug_info(
        string $source
    ) {
        # Arrange
        $base = 'entry1';

        $toCache = "$base.key1";
        $cachedArray = [$toCache => $this->getEntryValue($toCache)];

        $providedArray = $this->getConfigValues();

        $resolvedArray = $this->resolveDeferredValues($providedArray);

        $path = null;
        $storedArray = $providedArray;

        if ('directory' === $source) {
            $path = $this->getDataPath();
            $storedArray = [$base => $this->getEntryValue($base)];

            $this->sut = new Config($path);
        } elseif ('array' === $source) {
            $this->sut = new Config($storedArray);
        }

        # Act
        $this->sut->get($toCache);

        $result = $this->sut->__debugInfo();
        $data = $result['data'];

        # Assert
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('data', $result);

        $this->assertArrayHasKey('cached', $data);
        $this->assertArrayHasKey('stored', $data);
        $this->assertArrayHasKey('resolved', $data);
        $this->assertArrayHasKey('provided', $data);

        $this->assertSame($path, $result['path']);
        $this->assertSame($cachedArray, $data['cached']);
        $this->assertSame($storedArray, $data['stored']);
        $this->assertEquals($providedArray, $data['provided']);
        $this->assertEquals($resolvedArray, $data['resolved']);
    }

    public function debugInfoData(): array
    {
        return [
            'source=directory' => ['directory'],
            'source=array' => ['array'],
        ];
    }

    /**
     * @test
     * @dataProvider nestedSetValuesData
     */
    public function it_can_set_nested_values_without_overriding_parent(mixed $nested)
    {
        $key = 'entry1.set';
        $expected = $this->getResolvedConfigValues([
            'entry1' => [
                'set' => $nested,
            ],
        ]);

        $this->sut->set($key, $nested);

        $this->assertSame($expected, $this->sut->all());
    }

    public function nestedSetValuesData(): array
    {
        $this->initFaker();

        $deferred = $this->getDeferredMock();
        $deferred->method('resolve')->willReturn($this->unique->colorName());

        return [
            'type=scalar' => [$this->unique->sentence()],
            'type=array' => [[
                'key1' => $this->unique->address(),
                'key2' => $this->unique->userName(),
            ]],
            'type=deferred' => [$deferred],
        ];
    }
}
