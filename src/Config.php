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

    public function __debugInfo()
    {
        return [
            'cached' => $this->cache,
            'data' => $this->data->export(),
            'resolved' => $this->all(),
        ];
    }

    public function set(string $key, $value): void
    {
        $this->data->set($key, $value);
        $this->cache[$key] = $value;
    }

    public function get(string $key, $default = null): mixed
    {
        if ($this->hasCachedData($key)) {
            return $this->cache[$key];
        }

        $this->ensureBaseIsLoaded($key);

        if (!$this->data->has($key)) {
            return $default;
        }

        $value = $this->maybeResolveValue($this->data->get($key));

        return $this->cache[$key] = is_array($value)
            ? $this->fullyResolveArray($value)
            : $value;
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
        foreach (new DirectoryIterator($this->path) as $file) {
            if (
                'php' === $file->getExtension()
                && !$this->data->has($base = $file->getBasename('.php'))
            ) {
                $this->data->set($base, require $file->getPathname());
            }
        }

        return $this->fullyResolveArray($this->data->export());
    }

    protected function hasCachedData(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }

    protected function fullyResolveArray(array $values): array
    {
        array_walk_recursive(
            $values,
            fn (&$item) => $item = $this->maybeResolveValue($item)
        );

        return $values;
    }

    protected function maybeResolveValue($value): mixed
    {
        if ($value instanceof DeferredValueInterface) {
            $value = $value->resolve($this);
        }

        return $value;
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
}
