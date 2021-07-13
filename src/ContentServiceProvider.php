<?php

namespace Asseco\ContentFileStorageDriver;

/**
 * laravel Service Provider.
 */

use Asseco\Chassis\App\Facades\Iam;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as AbstractServiceProvider;
use League\Flysystem\Filesystem;

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
        Storage::extend(self::DRIVER_NAME, function ($app, $config) {
            $config['token'] = $config['token'] ?? Iam::getServiceToken();
            $client = new ContentClient($config['token'], $config['pathPrefix'], $config['baseURL'], $config['defaultRepository']);

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
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystems.php', 'filesystem.disks');
    }
}
