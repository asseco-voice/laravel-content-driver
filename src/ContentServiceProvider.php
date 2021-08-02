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
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystem.php', 'filesystem.disks');
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
            $client = new ContentClient($config['token'], $config['base_url'], $config['base_rest_api_url'], $config['default_repository']);

            return new Filesystem(
                new ContentAdapter($client)
            );
        });
    }
}
