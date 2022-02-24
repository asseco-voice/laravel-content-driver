<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Models\Document;
use Asseco\ContentFileStorageDriver\Models\Folder;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ContentClient
{
    public Folder $folder;
    public Document $document;

    private string $apiUrl;
    private string $repository;

    public function __construct(string $apiUrl, string $repository)
    {
        $this->apiUrl = $apiUrl;
        $this->repository = $repository;

        $this->folder = new Folder($this->url(), $this->repository);
        $this->document = new Document($this->url(), $this->repository);
    }

    protected function url(): string
    {
        return rtrim($this->apiUrl, '/') . '/' . $this->repository;
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
     * @return false|resource
     *
     * @throws Exception
     */
    public function readStream($path)
    {
        return $this->document->getStream($path);
    }

    /**
     * @param $path
     * @return string
     *
     * @throws Exception
     */
    public function readRaw($path): string
    {
        return $this->document->get($path)->body();
    }

    /**
     * @param string $path
     * @return bool
     *
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
     * @return array
     *
     * @throws Exception
     */
    public function tree(string $directory = '/', bool $recursive = false): array
    {
        $page = 1;
        $directories = [];

        do {
            $directoryList = $this->folder->listDirectory($directory, $recursive, $page);
            $directoryItems = Arr::get($directoryList, 'items');

            foreach ($directoryItems as $directoryItem) {
                $directories[] = [
                    'type'       => 'dir',
                    'path'       => $directoryItem['path'] . $directoryItem['name'],
                    'visibility' => 'public',
                    'size'       => 0,
                    'timestamp'  => 0,
                ];
            }

            $page++;
        } while ($directoryList['total-pages'] > $directoryList['page']);

        return $directories;
    }
}
