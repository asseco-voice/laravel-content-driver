<?php

use Illuminate\Support\Str;

return [
    'content' => [
        'driver'        => 'content-file-storage',
        'api_url'       => env('CONTENT_API_URL'),
        'repository'    => env('CONTENT_REPOSITORY', 'dms'),
        'root'          => env('CONTENT_ROOT', Str::snake(env('APP_NAME'))),
        'should_extend' => true,
    ],
];
