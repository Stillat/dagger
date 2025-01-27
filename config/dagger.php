<?php

return [
    'ctr' => [
        'unsafe_functions' => [
            // Date/Time related.
            'now', 'time', 'date',

            // Environment related.
            'env', 'getenv',

            // Request related.
            'cookie', 'request', 'session',

            // Debugging related.
            'dd', 'dump', 'var_dump', 'debug_backtrace', 'phpinfo',
        ],
    ],
];
