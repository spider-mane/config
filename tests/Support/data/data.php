<?php

use WebTheory\Config\Deferred\Reflection;

return [
    'scalar' => 'val1',
    'array' => [
        'sub1a' => [
            'sub2a' => 'nestedVal1',
        ],
    ],
    'deferred' => Reflection::get('data.resolved'),
    'resolved' => 'resolvedData',
];
