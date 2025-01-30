<?php

return [
    'ctr' => [
        'unsafe_variables' => [
            '$_GET', '$_POST', '$_FILES', '$_REQUEST', '$_SESSION',
            '$_ENV', '$_COOKIE', '$http_response_header', '$argc', '$argv',
        ],
        'unsafe_functions' => [
            'now', 'time', 'date', 'env', 'getenv', 'cookie',
            'request', 'session', 'dd', 'dump', 'var_dump',
            'debug_backtrace', 'phpinfo', 'extract',
            'get_defined_vars', 'parse_str',
        ],
    ],
];
