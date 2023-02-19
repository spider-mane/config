<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\Support\Concerns\FakerTrait;
use Tests\Support\Concerns\HelperTrait;
use Tests\Support\Concerns\MockeryTrait;

class TestCase extends PHPUnitTestCase
{
    use FakerTrait;
    use HelperTrait;
    use MockeryTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initFaker();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->closeMockery();
    }

    protected function getSupportPath(string $path = '')
    {
        return __DIR__ . $path;
    }
}
