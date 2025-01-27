<?php

return [
    'ctr' => [
        'unsafe_variables' => [
            '$_GET', '$_POST', '$_FILES', '$_REQUEST', '$_SESSION',
            '$_ENV', '$_COOKIE', '$http_response_header', '$argc', '$argv',
        ],
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
