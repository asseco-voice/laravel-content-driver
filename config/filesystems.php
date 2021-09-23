<?php

use Illuminate\Support\Str;

return [
    'content' => [
        'driver'     => 'content-file-storage',
        'api_url'    => env('CONTENT_API_URL'),
        'prefix'     => env('CONTENT_PREFIX', env('APP_NAME')),
        'repository' => env('CONTENT_REPOSITORY', 'dms'),
        'root'       => Str::snake(env('APP_NAME')),
    ],
];
