<?php

namespace WebTheory\Config;

use Dflydev\DotAccessData\Data;
use Dflydev\DotAccessData\DataInterface;
use DirectoryIterator;
use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Config implements ConfigInterface
{
    protected string $path;

    protected DataInterface $data;

    protected array $cache = [];

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->data = new Data();
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
            $this->updateDataCache($key, $value);
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

    protected function hasCachedData(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }

    protected function getCachedData(string $key): mixed
    {
        return $this->cache[$key];
    }

    protected function updateDataCache(string $key, mixed $data): void
    {
        if (!is_array($data)) {
            $this->cache[$key] = $data;
        }
    }

    protected function processValue(mixed $value, string $key): mixed
    {
        if ($value instanceof DeferredValueInterface) {
            $value = $value->resolve($this);
        } elseif (is_array($value)) {
            $value = $this->processArray($value, $key);
        }

        $this->updateDataCache($key, $value);

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
