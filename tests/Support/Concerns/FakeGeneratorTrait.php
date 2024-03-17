<?php

namespace Tests\Support\Concerns;

trait FakeGeneratorTrait
{
    use FakerTrait;
    use GeneratorTrait;

    protected function fakeGeneratorSet(string ...$types): array
    {
        return array_map(
            fn ($type) => fn () => $this->fake->{$type}(),
            $types
        );
    }

    protected function uniqueGeneratorSet(string ...$types): array
    {
        return array_map(
            fn ($type) => fn () => $this->unique->{$type}(),
            $types
        );
    }

    protected function fakeValueFactory(string ...$types): callable
    {
        return $this->valueFactory(...$this->fakeGeneratorSet(...$types));
    }

    protected function uniqueValueFactory(string ...$types): callable
    {
        return $this->valueFactory(...$this->uniqueGeneratorSet(...$types));
    }
}
