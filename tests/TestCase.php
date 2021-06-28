<?php

declare(strict_types=1);

namespace Asseco\ContentFileStorageDriver\Tests;

use Asseco\ContentFileStorageDriver\ContentAdapter;
use Asseco\ContentFileStorageDriver\ContentClient;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @var array
     */
    protected array $config;

    public function setUp(): void
    {
        parent::setUp();
        $this->config = require __DIR__ . '/config/config.testing.php';
    }

    /**
     * @return ContentClient
     */
    protected function getClientInstance(): ContentClient
    {
        return new ContentClient(
            $this->config['baseURL'],
            $this->config['baseRestAPIUrl'],
            $this->config['defaultRepository']
        );
    }

    /**
     * @return ContentAdapter
     */
    protected function getAdapterInstance(): ContentAdapter
    {
        return new ContentAdapter($this->getClientInstance());
    }
}
