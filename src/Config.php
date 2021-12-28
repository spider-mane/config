<?php

namespace WebTheory\Config;

use Arrayy\Arrayy;
use Arrayy\StaticArrayy;
use Illuminate\Support\Arr;
use Noodlehaus\Config as NoodlehausConfig;
use Noodlehaus\Parser\ParserInterface;
use WebTheory\Config\Interfaces\ConfigInterface;
use WebTheory\Config\Interfaces\DeferredValueInterface;

class Config extends NoodlehausConfig implements ConfigInterface
{
    public function __construct($values, ParserInterface $parser = null, $string = false)
    {
        parent::__construct($values, $parser, $string);
        $this->resolveReflections();
    }

    /**
     * {@inheritDoc}
     */
    protected function loadFromFile($path, ParserInterface $parser = null)
    {
        $paths = $this->getValidPath($path);
        $this->data = [];

        foreach ($paths as $path) {
            // Get file information
            $info = pathinfo($path);
            $parts = explode('.', $info['basename']);
            $entry = preg_replace("/[^A-Za-z0-9 ]/", '_', $parts[0]);

            if ($parser === null) {
                $extension = array_pop($parts);

                // Skip the `dist` extension
                if ($extension === 'dist') {
                    $extension = array_pop($parts);
                }

                // Get file parser
                $parser = $this->getParser($extension);

                // Try to load file
                $this->data = array_replace_recursive($this->data, [$entry => $parser->parseFile($path)]);

                // Clean parser
                $parser = null;
            } else {
                // Try to load file using specified parser
                $this->data = array_replace_recursive($this->data, [$entry => $parser->parseFile($path)]);
            }
        }
    }

    protected function resolveReflections()
    {
        array_walk_recursive($this->data, function (&$entry) {
            if ($entry instanceof DeferredValueInterface) {
                $entry = $entry->defer($this);
            }
        });
    }

    public function has($key)
    {
        if (isset($this->cache[$key])) {
            return true;
        }

        if ($exists = Arr::has($this->data, $key)) {
            $this->cache[$key] = Arr::get($this->data, $key);
        }

        return $exists;
    }

    // public function get($key, $default = null)
    // {
    //     if ($this->has($key)) {
    //         $this->resolveDeferredValues($key, $this->cache[$key]);

    //         return $this->cache[$key];
    //     }

    //     return $default;
    // }

    // protected function resolveDeferredValues($key, &$value)
    // {
    //     if ($value instanceof DeferredValueInterface) {
    //         $value = $value->defer($this);
    //     }

    //     if (is_array($value)) {
    //         foreach ($value as $nested => &$value) {
    //             $this->resolveDeferredValues("$key.$nested", $value);
    //         }
    //     }

    //     $this->resolved[] = $key;
    // }
}
