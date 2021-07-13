<?php

declare(strict_types=1);

namespace Asseco\ContentFileStorageDriver\Tests;

use Asseco\Chassis\App\Facades\Iam;
use Asseco\ContentFileStorageDriver\ContentAdapter;
use Asseco\ContentFileStorageDriver\ContentClient;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @var array
     */
    public array $config;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return ContentClient
     */
    protected function getClientInstance(): ContentClient
    {
        $this->config['token'] = $this->config['token'] ?? $this->getToken();

        return new ContentClient(
            $this->config['token'],
            env('FILESYSTEM_BASE_URL'),
            env('FILESYSTEM_PATH_PREFIX'),
            env('FILESYSTEM_DEFAULT_REPOSITORY'),
        );
    }

    /**
     * @return ContentAdapter
     */
    protected function getAdapterInstance(): ContentAdapter
    {
        return new ContentAdapter($this->getClientInstance());
    }

    protected function getToken(): string
    {
        return Iam::getServiceToken();
    }
}
