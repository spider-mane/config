<?php

namespace WebTheory\Config;

use Dflydev\DotAccessData\Data;
use Dflydev\DotAccessData\DataInterface;
use DirectoryIterator;
use UnexpectedValueException;
use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Config implements ConfigInterface
{
    protected DataInterface $data;

    protected array $cache = [];

    protected string $path;

    /**
     * @param string|array $source The source where values are to be retrieved
     * from. May be an array, the path to a php file that returns an array, or
     * the path to a directory containing such files.
     */
    public function __construct(string|array $source)
    {
        if (is_array($source)) {
            $this->data = new Data($source);
        } elseif (is_file($source)) {
            $this->data = new Data(require $source);
        } elseif (is_dir($source)) {
            $this->path = $source;
            $this->data = new Data();
        } else {
            $this->assertSourcePathInvalid($source);
        }
    }

    public function __debugInfo(): array
    {
        $proxy = new static($this->path);

        return [
            'path' => $this->path,
            'data' => [
                'cached' => $this->cache,
                'current' => $this->data->export(),
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

        $this->ensureBaseIsLoaded($key);

        return $this->data->has($key);
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

    protected function ensureBaseIsLoaded(string $key): void
    {
        if (!$this->hasPath()) {
            return;
        }

        $parts = explode('.', str_replace('/', '.', $key));
        $base = $parts[0];

        if (!$this->data->has($base)) {
            $file = "{$this->path}/{$base}.php";

            if (file_exists($file)) {
                $this->data->set($base, (require $file));
            }
        }
    }

    /**
     * @return $this
     */
    protected function loadAllBases(): Config
    {
        if (!$this->hasPath()) {
            return $this;
        }

        foreach (new DirectoryIterator($this->path) as $file) {
            if (
                'php' === $file->getExtension()
                && !$this->data->has($base = $file->getBasename('.php'))
            ) {
                $this->data->set($base, require $file->getPathname());
            }
        }

        return $this;
    }
}
