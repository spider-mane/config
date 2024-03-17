<?php

namespace WebTheory\Config\Abstracts;

use WebTheory\Config\Interfaces\ConfigInterface;

abstract class AbstractStackedConfig implements ConfigInterface
{
    /**
     * @var array<ConfigInterface>
     */
    protected array $stack;

    public function has(string $key): bool
    {
        foreach ($this->stack as $config) {
            if ($config->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $found = [];
        $count = 0;
        $asList = 0;
        $asMap = 0;

        foreach ($this->stack as $config) {
            if (!$config->has($key)) {
                continue;
            }

            $found[] = $value = $config->get($key);

            $count++;

            if (is_array($value)) {
                if (array_is_list($value)) {
                    $asList++;
                } else {
                    $asMap++;
                }
            }
        }

        if ($asList && !$asMap) {
            $callback = fn ($item) => is_array($item) ? $item : [$item];
            $value = array_merge(...array_map($callback, $found));
        } elseif ($count === $asMap) {
            $value = array_merge(...array_reverse($found));
            $callback = function (&$value, $entry, $base) {
                $value = $this->get("{$base}.{$entry}");
            };

            array_walk_recursive($value, $callback, $key);
        } elseif ($found) {
            $value = $found[0];
        } else {
            $value = $default;
        }

        return $value;
    }

    public function all(): array
    {
        return array_replace_recursive(...array_map(
            fn (ConfigInterface $config) => $config->all(),
            array_reverse($this->stack)
        ));
    }

    abstract protected function getPrimaryConfig(): ConfigInterface;
}
