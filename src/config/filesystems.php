<?php

return [

    'default' => env('FILESYSTEM_DRIVER', 'content'),

    'disks' => [

        'content' => [
            'driver' => 'content-file-storage',
            'token' => '',
            'baseURL' => env('FILESYSTEM_BASE_URL'),
            'baseRestAPIUrl' => env('FILESYSTEM_BASE_REST_API_URL'),
            'defaultRepository' => env('FILESYSTEM_DEFAULT_REPOSITORY'),
        ],

    ],

];
