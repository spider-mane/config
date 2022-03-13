<?php

use WebTheory\Config\Deferred\Reflection;

return [
    'scalar' => 'val1',
    'deferred' => Reflection::get('data.key1'),
    'array' => [
        'sub1a' => [
            'sub2a' => 'nestedVal1',
        ],
    ],
];
