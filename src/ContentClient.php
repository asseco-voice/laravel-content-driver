<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Models\Document;
use Asseco\ContentFileStorageDriver\Models\Folder;
use Asseco\ContentFileStorageDriver\Responses\ContentItemList;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ContentClient
{
    public Folder $folder;
    public Document $document;

    private string $apiUrl;
    private string $prefix;
    private string $repository;
    private PendingRequest $client;

    public function __construct(string $apiUrl, string $prefix, string $repository)
    {
        $this->apiUrl = $apiUrl;
        $this->prefix = $prefix;
        $this->repository = $repository;

        $this->client = Http::withToken(request()->bearerToken())
            ->withHeaders([
                'Allow'        => 'application/json',
            ]);

        $this->folder = new Folder($this->client, $this->url(), $this->prefix);
        $this->document = new Document($this->client, $this->url(), $this->prefix);
    }

    protected function url(): string
    {
        return $this->apiUrl . $this->repository;
    }

    public function upload(string $path, $contents, bool $overwrite = false)
    {
        $url = $this->folder->createAndReturnUrl($path);

        return $this->document->upload($url, $path, $contents, $overwrite);
    }

    public function uploadStream(string $path, $contents, bool $overwrite = false)
    {
        $url = $this->folder->createAndReturnUrl($path);

        return $this->document->uploadStream($url, $path, $contents, $overwrite);
    }

    /**
     * @param $path
     *
     * @return false|resource
     * @throws Exception
     */
    public function readStream($path)
    {
        return $this->document->getStream($path);
    }

    /**
     * @param $path
     *
     * @return string
     * @throws Exception
     */
    public function readRaw($path): string
    {
        return $this->document->get($path)->body();
    }

    /**
     * @param string $path
     * @return bool
     * @throws Exception
     */
    public function delete(string $path): bool
    {
        $response = $this->document->delete($path);

        return $response->status() === JsonResponse::HTTP_OK;
    }

    /**
     * @param string $directory
     * @param bool $recursive
     *
     * @return iterable
     * @throws Exception
     */
    public function tree(string $directory = '/', bool $recursive = false): iterable
    {
        $page = 1;

        do {
            yield $this->folder->listDirectory($directory, $recursive, 10, ++$page);
        } while ($this->responseHasNextPage(
            new ContentItemList(
                $this->folder->listDirectory($directory, $recursive, 10, ++$page)
            )
        ));
    }

    /**
     * @param ContentItemList $response
     *
     * @return bool
     */
    protected function responseHasNextPage(ContentItemList $response): bool
    {
        return $response->totalPages > 0 && $response->page != $response->totalPages;
    }
}
