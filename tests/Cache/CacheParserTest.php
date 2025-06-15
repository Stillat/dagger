<?php

use Stillat\Dagger\Cache\CacheAttributeParser;

test('it parses cache details', function ($cacheString, $results) {
    $attributeParser = new CacheAttributeParser;

    $expectedDuration = $results[0];
    $expectedStore = $results[1];
    $expectedExtraParams = $results[2];

    $parseResults = $attributeParser->parseCacheString($cacheString);

    expect($parseResults->duration)->toBe($expectedDuration)
        ->and($parseResults->store)->toBe($expectedStore)
        ->and($parseResults->args)->toBe($expectedExtraParams);
})->with([
    ['cache', ['forever', 'array', []]],
    ['cache.forever', ['forever', 'array', []]],
    ['cache.forever.array', ['forever', 'array', []]],
    ['forever.array1', ['forever', 'array1', []]],
    ['forever.array1', ['forever', 'array1', []]],
    ['flexible:5:10', ['flexible', 'array', ['5', '10']]],
    ['cache.flexible:5:10', ['flexible', 'array', ['5', '10']]],
    ['cache.flexible:15:150', ['flexible', 'array', ['15', '150']]],
    ['cache.100', ['100', 'array', []]],
    ['100', ['100', 'array', []]],
    ['100.file', ['100', 'file', []]],
    ['cache.100.file', ['100', 'file', []]],
    ['42', ['42', 'array', []]],
    ['42.file', ['42', 'file', []]],
    ['cache.42.file', ['42', 'file', []]],
    [
        'cache.1y.file',
        [
            [1, 0, 0, 0, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.2mo.file',
        [
            [0, 2, 0, 0, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.3w.file',
        [
            [0, 0, 3, 0, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.4d.file',
        [
            [0, 0, 0, 4, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.5h.file',
        [
            [0, 0, 0, 0, 5, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.6m.file',
        [
            [0, 0, 0, 0, 0, 6, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.7s.file',
        [
            [0, 0, 0, 0, 0, 0, 7],
            'file',
            [],
        ],
    ],
    [
        'cache.1y2mo.file',
        [
            [1, 2, 0, 0, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.1y2mo3w.file',
        [
            [1, 2, 3, 0, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.1y2mo3w4d.file',
        [
            [1, 2, 3, 4, 0, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.1y2mo3w4d5h.file',
        [
            [1, 2, 3, 4, 5, 0, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.1y2mo3w4d5h6m.file',
        [
            [1, 2, 3, 4, 5, 6, 0],
            'file',
            [],
        ],
    ],
    [
        'cache.1y2mo3w4d5h6m7s.file',
        [
            [1, 2, 3, 4, 5, 6, 7],
            'file',
            [],
        ],
    ],
    [
        'cache.1d2h3m4s.file',
        [
            [0, 0, 0, 1, 2, 3, 4],
            'file',
            [],
        ],
    ],
    [
        '1y2mo3w4d5h6m7s.database',
        [
            [1, 2, 3, 4, 5, 6, 7],
            'database',
            [],
        ],
    ],
]);
