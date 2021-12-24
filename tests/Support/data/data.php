<?php

use WebTheory\Config\Deferred\Reflection;

return [
    'key1' => 'val1',
    'key2' => 'val2',
    'key3' => Reflection::get('data.key1'),
];
