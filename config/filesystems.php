<?php

return [
    'content' => [
        'driver'             => 'content-file-storage',
        'base_url'           => env('CONTENT_BASE_URL'),
        'base_rest_api_url'  => env('CONTENT_BASE_REST_API_URL'),
        'default_repository' => env('CONTENT_DEFAULT_REPOSITORY'),
    ],
];
