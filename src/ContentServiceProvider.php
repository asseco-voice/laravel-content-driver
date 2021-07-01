<?php

namespace Asseco\ContentFileStorageDriver;

/**
 * laravel Service Provider.
 */

use Illuminate\Support\ServiceProvider as AbstractServiceProvider;
use League\Flysystem\Filesystem;
use Storage;

class ContentServiceProvider extends AbstractServiceProvider
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
        //$this->publishes([__DIR__ . '/../config/filesystem.php' => config_path('filesystem')]);

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
        //$this->mergeConfigFrom(__DIR__ . '/../config/filesystem.php', 'filesystem');
    }
}
