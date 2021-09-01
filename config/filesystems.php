<?php

return [
    'content' => [
        'driver' => 'content-file-storage',
        'pathPrefix' => env('FILESYSTEM_PATH_PREFIX'),
        'restAPIUrl' => env('FILESYSTEM_REST_API_URL'),
        'defaultRepository' => env('FILESYSTEM_DEFAULT_REPOSITORY'),
    ],
];
