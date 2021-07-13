<?php

return [
    'content' => [
        'driver' => 'content-file-storage',
        'token' => '',
        'pathPrefix' => env('FILESYSTEM_PATH_PREFIX'),
        'baseURL' => env('FILESYSTEM_BASE_URL'),
        'defaultRepository' => env('FILESYSTEM_DEFAULT_REPOSITORY'),
    ],
];
