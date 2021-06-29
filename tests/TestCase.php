<?php

declare(strict_types=1);

namespace Asseco\ContentFileStorageDriver\Tests;

use Asseco\ContentFileStorageDriver\ContentAdapter;
use Asseco\ContentFileStorageDriver\ContentClient;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @var array
     */
    protected array $config;

    public function setUp(): void
    {
        $this->config = require __DIR__ . '/config/config.testing.php';
        parent::setUp();
    }

    /**
     * @return ContentClient
     */
    protected function getClientInstance(): ContentClient
    {
        $this->config['token'] = $this->getToken();

        return new ContentClient(
            $this->config['token'],
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

    protected function getToken(): string
    {
        return Http::asForm()
            ->withHeaders([
                'Allow' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->POST(
                'http://10.135.11.98:7072/auth/realms/evil/protocol/openid-connect/token',
                [
                    'client_id'             => 'livepoc_web',
                    'grant_type'            => 'password',
                    'username'              => 'live',
                    'password'              => 'live'
                ]
            )->object()->access_token;
    }
}
