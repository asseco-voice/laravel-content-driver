<?php

return [
    'content' => [
        'driver'     => 'content-file-storage',
        'api_url'    => env('CONTENT_API_URL'),
        'prefix'     => env('CONTENT_PREFIX', env('APP_NAME')),
        'repository' => env('CONTENT_REPOSITORY', 'dms'),
    ],
];
