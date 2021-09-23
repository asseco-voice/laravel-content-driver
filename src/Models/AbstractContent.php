<?php

namespace Asseco\ContentFileStorageDriver\Models;

use Asseco\ContentFileStorageDriver\Responses\ContentItem;
use Illuminate\Http\Client\PendingRequest;

abstract class AbstractContent
{
    protected PendingRequest $client;
    protected string $url;
    protected string $repository;

    public function __construct(PendingRequest $client, string $url, string $repository)
    {
        $this->client = $client;
        $this->url = $url;
        $this->repository = $repository;
    }

    public function url()
    {
        $url = rtrim($this->url, '/');

        return "$url/";
    }

    public function resourceUrl()
    {
        $resource = trim($this->apiResourceName(), '/');

        return "{$this->url()}$resource";
    }

    abstract public function apiResourceName();

    abstract public function responseClass(): string;

    public function metadataById(string $id): ContentItem
    {
        $url = $this->idMetadataUrl($id);

        $response = $this->client->get($url)->throw()->json();

        $responseClass = $this->responseClass();

        return new $responseClass($response);
    }

    protected function idMetadataUrl(string $id): string
    {
        return "{$this->resourceUrl()}/$id/metadata";
    }

    public function metadataByPath(string $path): ContentItem
    {
        $url = $this->pathMetadataUrl($path);

        $response = $this->client->get($url)->throw()->json();

        $responseClass = $this->responseClass();

        return new $responseClass($response);
    }

    protected function pathMetadataUrl(string $path): string
    {
        return "{$this->url()}/$path/metadata";
    }
}
