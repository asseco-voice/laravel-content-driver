<?php

namespace Asseco\ContentFileStorageDriver;

/**
 * laravel Service Provider.
 */

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider as AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    /**
     * @var string The name of the driver.
     */
    const DRIVER_NAME = 'content-file-storage';

    /**
     * Bootstrap the application services.
     * Extend the storage filesystem withe the new driver.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend(self::DRIVER_NAME, function ($app, $config) {

            $token = Iam::getServiceToken();
            $client = new ContentClient($token, $config['baseURL'], $config['baseRestAPIUrl'], $config['defaultRepository']);

            return new Filesystem(
                new ContentAdapter($client)
            );
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // No services to register.
    }
}
