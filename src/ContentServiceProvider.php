<?php

namespace Asseco\ContentFileStorageDriver;

use Illuminate\Filesystem\FilesystemAdapter;
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
     * Extend the storage filesystem with the new driver.
     *
     * @return void
     */
    public function boot()
    {
        if (config('filesystems.disks.content.should_extend')) {
            Storage::extend('content-file-storage', function ($app, $config) {
                $client = new ContentClient(
                    $config['api_url'],
                    $config['repository']
                );

                $adapter = new ContentAdapter($client, $config['root']);

                return new FilesystemAdapter(
                    new Filesystem($adapter, $config),
                    $adapter,
                    $config
                );
            });
        }
    }
}
