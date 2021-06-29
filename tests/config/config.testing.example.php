<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flysystem Adapter for Content configurations
    |--------------------------------------------------------------------------
    |
    | These configurations will be used in all the the tests to bootstrap
    | a Client object.
    |
    */

    /**
     * Personal access token.
     */
    'token'                 => null,

    /**
     * Base URL of Content server you want to use.
     */
    'baseURL'               => null,

    /**
     * RestAPI that should be used.
     */
    'baseRestAPIUrl'        => '/v1/content/',

    /**
     * Repository that should be used for default.
     */
    'defaultRepository'     => 'dms',
];
