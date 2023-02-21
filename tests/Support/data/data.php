<?php

use WebTheory\Config\Deferred\Reflection;

return [
    'scalar' => 'value',
    'array' => [
        'scalar' => 'value',
        'array' => [
            'scalar' => 'value',
        ],
    ],
    'deferred' => Reflection::get('data.resolved'),
    'resolved' => 'resolvedData',
];
