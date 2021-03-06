<?php

declare(strict_types=1);

/**
 * Heroku settings, overwrites values from settings.php.
 */

return [
    'guzzle' => [
        'cache' => [
            'dir' => '/tmp/http'
        ],
    ],
    'di' => [
        'cache_dir' => '/tmp/di'
    ],
];
