<?php

namespace Asseco\ContentFileStorageDriver;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as AbstractServiceProvider;
use League\Flysystem\Filesystem;

class ContentServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystems.php', 'filesystems.disks');
    }

    /**
     * Bootstrap the application services.
     * Extend the storage filesystem withe the new driver.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('content-file-storage', function ($app, $config) {
            $client = new ContentClient(
                $config['api_url'],
                $config['prefix'],
                $config['repository']
            );

            return new Filesystem(new ContentAdapter($client, $config['root']));
        });
    }
}
