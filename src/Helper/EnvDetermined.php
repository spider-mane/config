<?php

namespace WebTheory\Config\Helper;

use Env\Env;

class EnvDetermined
{
    public function __construct(
        protected string $envVar = 'APP_ENV',
        protected string $defaultEnv = 'production',
        protected string $defaultContext = 'default'
    ) {
        //
    }

    public function process(array $conditions): mixed
    {
        $env = strtolower($this->fetchVar($this->envVar, $this->defaultEnv));
        $default = $conditions['@' . $this->defaultContext] ?? [];
        $mod = $conditions['@' . $env];
        $resolved = array_merge_recursive($default, $mod);

        return $resolved;
    }

    protected function fetchVar(string $name, $default = null): mixed
    {
        return Env::get($name) ?? $default;
    }
}
