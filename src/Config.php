<?php

namespace WebTheory\Config;

use Dflydev\DotAccessData\Data;
use Dflydev\DotAccessData\DataInterface;
use FilesystemIterator;
use UnexpectedValueException;
use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Config implements ConfigInterface
{
    protected DataInterface $data;

    protected array $cache = [];

    protected string $path;

    /**
     * Creates a new instance
     *
     * @param string|array $source The source from which values are to be
     * retrieved. May be an array, the path of a php file that returns an array,
     * or the path of a directory containing such files.
     */
    public function __construct(string|array $source = [])
    {
        if (is_array($source)) {
            $this->data = new Data($source);
        } elseif (is_file($source)) {
            $this->data = new Data($this->require($source));
        } elseif (is_dir($source)) {
            $this->path = $source;
            $this->data = new Data();
        } else {
            $this->assertSourcePathInvalid($source);
        }
    }

    public function __debugInfo(): array
    {
        // using a proxy to extract data that requires state changing operations
        $proxy = new static();
        $proxy->data = new Data($stored = $this->data->export());

        if ($this->hasPath()) {
            $proxy->path = $this->path;
        }

        return [
            'path' => $this->path ?? null,
            'data' => [
                'cached' => $this->cache,
                'stored' => $stored,
                'provided' => $proxy->loadAllBases()->data->export(),
                'resolved' => $proxy->all(),
            ],
        ];
    }

    public function set(string $key, mixed $value): void
    {
        $this->data->set($key, $value);

        if (!($value instanceof DeferredValueInterface)) {
            $this->maybeUpdateCachedData($key, $value);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->hasCachedData($key)) {
            return $this->getCachedData($key);
        }

        $this->ensureBaseIsLoaded($key);

        if (!$this->data->has($key)) {
            return $default;
        }

        return $this->processValue($this->data->get($key), $key);
    }

    public function has(string $key): bool
    {
        if ($this->hasCachedData($key)) {
            return true;
        }

        return $this->ensureBaseIsLoaded($key)->data->has($key);
    }

    public function all(): array
    {
        return $this->loadAllBases()->processArray($this->data->export());
    }

    protected function assertSourcePathInvalid(string $path): void
    {
        throw new UnexpectedValueException(
            "{$path} is not a valid filename or directory."
        );
    }

    protected function hasPath(): bool
    {
        return isset($this->path);
    }

    protected function hasCachedData(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }

    protected function getCachedData(string $key): mixed
    {
        return $this->cache[$key];
    }

    protected function updateCachedData(string $key, mixed $data): void
    {
        $this->cache[$key] = $data;
    }

    protected function maybeUpdateCachedData(string $key, mixed $data): void
    {
        if (!is_array($data)) {
            $this->updateCachedData($key, $data);
        }
    }

    protected function processValue(mixed $value, string $key): mixed
    {
        if ($value instanceof DeferredValueInterface) {
            $value = $value->resolve($this);
        } elseif (is_array($value)) {
            $value = $this->processArray($value, $key);
        }

        $this->maybeUpdateCachedData($key, $value);

        return $value;
    }

    protected function processArray(array $values, string $key = ''): array
    {
        $base = empty($key) ? $key : $key . '.';

        foreach ($values as $key => &$entry) {
            $entry = $this->processValue($entry, $base . $key);
        }

        return $values;
    }

    /**
     * @return $this
     */
    protected function ensureBaseIsLoaded(string $key): static
    {
        if (!$this->hasPath()) {
            return $this;
        }

        $parts = explode('.', str_replace('/', '.', $key));
        $base = $parts[0];
        $file = "{$this->path}/{$base}.php";

        if (!$this->data->has($base) && file_exists($file)) {
            $this->setFromFile($base, $file);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function loadAllBases(): static
    {
        if (!$this->hasPath()) {
            return $this;
        }

        foreach (new FilesystemIterator($this->path) as $file) {
            $base = $file->getBasename('.php');

            if ('php' === $file->getExtension() && !$this->data->has($base)) {
                $this->setFromFile($base, $file->getPathname());
            }
        }

        return $this;
    }

    protected function setFromFile(string $key, string $file): void
    {
        $this->set($key, $this->require($file));
    }

    protected function require(string $file): mixed
    {
        static $enclose;

        $enclose ??= static fn ($__file) => require $__file;

        return $enclose($file);
    }
}
