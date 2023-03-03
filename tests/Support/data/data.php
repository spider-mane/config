<?php

use WebTheory\Config\Deferred\Reflection;

return [
    'scalar' => 'value1',
    'array' => [
        'scalar' => 'value2',
        'array' => [
            'scalar' => 'value3',
        ],
    ],
    'deferred' => Reflection::get('data.resolved'),
    'resolved' => 'value4',
];
